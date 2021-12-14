<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Service\Search;

use Magento\MagentoCloud\App\Error;
use Magento\MagentoCloud\Service\ServiceException;
use Magento\MagentoCloud\Service\ServiceInterface;
use Magento\MagentoCloud\Config\Environment;
use Magento\MagentoCloud\Http\ClientFactory;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * Returns search service configurations for ElasticSearch family engines.
 */
abstract class AbstractService implements ServiceInterface
{
    protected const RELATIONSHIP_KEY = 'abstractsearch';
    protected const ENGINE_SHORT_NAME = 'AS';
    public const ENGINE_NAME = 'abstractsearch';

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
     * Checks if Search Engine relationship is present.
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
        return $this->environment->getRelationship(static::RELATIONSHIP_KEY)[0] ?? [];
    }

    /**
     * Returns version of search service.
     * Returns 0 if response from search service doesn't contain version number or
     *   search service doesn't exist in relationships.
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
                    $asConfiguration = $this->call(sprintf(
                        '%s:%s',
                        $this->getHost(),
                        $this->getPort()
                    ));
                    $this->version = $asConfiguration['version']['number'];
                }
            } catch (Throwable $exception) {
                throw new ServiceException(
                    'Can\'t get version of ' .static::ENGINE_NAME. ': ' . $exception->getMessage(),
                    static::ENGINE_SHORT_NAME === 'ES'
                        ? Error::DEPLOY_ES_CANNOT_CONNECT
                        : Error::DEPLOY_OS_CANNOT_CONNECT
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
            throw new ServiceException(static::ENGINE_SHORT_NAME . ' service is not installed');
        }

        if (!empty($this->getConfiguration()['scheme'])) {
            return $this->getConfiguration()['scheme'] . '://' . $this->getConfiguration()['host'];
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
            throw new ServiceException(static::ENGINE_SHORT_NAME . ' service is not installed');
        }

        return (string)$this->getConfiguration()['port'];
    }

    /**
     * Checks if authentication is enabled: password and username exists in configuration
     *
     * @return bool
     */
    public function isAuthEnabled(): bool
    {
        return !empty($this->getConfiguration()['password']) && !empty($this->getConfiguration()['username']);
    }

    /**
     * Returns additional options for request to search service
     *
     * @return array
     */
    private function getRequestOptions(): array
    {
        if (!$this->isAuthEnabled()) {
            return [];
        }

        return [
            'auth' => [
                $this->getConfiguration()['username'],
                $this->getConfiguration()['password']
            ]
        ];
    }

    /**
     * Return full version with engine name.
     *
     * @return string
     * @throws ServiceException
     */
    abstract public function getFullEngineName(): string;

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
                $this->getHost(),
                $config['port']
            ));

            return $templates ? reset($templates)['settings'] : [];
        } catch (\Exception $exception) {
            $this->logger->warning(
                'Can\'t get configuration of ' . static::ENGINE_NAME . ': ' . $exception->getMessage()
            );

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
        $response = $this->clientFactory->create()->get($endpoint, $this->getRequestOptions());
        $templates = $response->getBody()->getContents();

        return json_decode($templates, true);
    }
}
