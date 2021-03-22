<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Service;

use Composer\Semver\Semver;
use Magento\MagentoCloud\App\Error;
use Magento\MagentoCloud\Config\Environment;
use Magento\MagentoCloud\Http\ClientFactory;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * Returns ElasticSearch service configurations.
 */
class ElasticSearch implements ServiceInterface
{
    private const RELATIONSHIP_KEY = 'elasticsearch';
    public const ENGINE_NAME = 'elasticsearch';

    /**
     * @var ClientFactory
     */
    private $clientFactory;

    /**
     * @var Environment
     */
    private $environment;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var string
     */
    private $version;

    /**
     * @param Environment $environment
     * @param ClientFactory $clientFactory
     * @param LoggerInterface $logger
     */
    public function __construct(
        Environment $environment,
        ClientFactory $clientFactory,
        LoggerInterface $logger
    ) {
        $this->environment = $environment;
        $this->clientFactory = $clientFactory;
        $this->logger = $logger;
    }

    /**
     * Checks if ES relationship is present.
     *
     * @return bool
     */
    public function isInstalled(): bool
    {
        return (bool)$this->getConfiguration();
    }

    /**
     * Retrieves configuration from relationship.
     *
     * @return array
     */
    public function getConfiguration(): array
    {
        return $this->environment->getRelationship(self::RELATIONSHIP_KEY)[0] ?? [];
    }

    /**
     * Returns version of elasticsearch service.
     * Returns 0 if response from elasticsearch doesn't contain version number or
     *   elasticsearch doesn't exist in relationships.
     *
     * @return string
     *
     * @throws ServiceException
     */
    public function getVersion(): string
    {
        if (!$this->isInstalled()) {
            return '0';
        }

        if ($this->version === null) {
            try {
                $config = $this->getConfiguration();
                if (isset($config['type']) && strpos($config['type'], ':') !== false) {
                    $this->version = explode(':', $config['type'])[1];
                } else {
                    $esConfiguration = $this->call(sprintf(
                        '%s:%s',
                        $this->getHost(),
                        $this->getPort()
                    ));
                    $this->version = $esConfiguration['version']['number'];
                }
            } catch (Throwable $exception) {
                throw new ServiceException(
                    'Can\'t get version of elasticsearch: ' . $exception->getMessage(),
                    Error::DEPLOY_ES_CANNOT_CONNECT
                );
            }
        }

        return $this->version;
    }

    /**
     * Retrieve host.
     *
     * @return string
     * @throws ServiceException
     */
    public function getHost(): string
    {
        if (!$this->isInstalled()) {
            throw new ServiceException('ES service is not installed');
        }

        return (string)$this->getConfiguration()['host'];
    }

    /**
     * Retrieve port.
     *
     * @return string
     * @throws ServiceException
     */
    public function getPort(): string
    {
        if (!$this->isInstalled()) {
            throw new ServiceException('ES service is not installed');
        }

        return (string)$this->getConfiguration()['port'];
    }

    /**
     * Return full version with engine name.
     *
     * @return string
     * @throws ServiceException
     */
    public function getFullVersion(): string
    {
        $version = $this->getVersion();

        if (Semver::satisfies($version, '>= 5')) {
            return self::ENGINE_NAME . (int)$version;
        }

        return self::ENGINE_NAME;
    }

    /**
     * Retrieves default template configuration.
     * May contain configuration for replicas and shards.
     *
     * @return array
     */
    public function getTemplate(): array
    {
        $config = $this->getConfiguration();

        if (!$config) {
            return [];
        }

        try {
            $templates = $this->call(sprintf(
                '%s:%s/_template/platformsh_index_settings',
                $config['host'],
                $config['port']
            ));

            return $templates ? reset($templates)['settings'] : [];
        } catch (\Exception $exception) {
            $this->logger->warning('Can\'t get configuration of elasticsearch: ' . $exception->getMessage());

            return [];
        }
    }

    /**
     * Call endpoint and return response.
     *
     * @param string $endpoint
     * @return array
     */
    private function call(string $endpoint): array
    {
        $response = $this->clientFactory->create()->get($endpoint);
        $templates = $response->getBody()->getContents();

        return json_decode($templates, true);
    }
}
