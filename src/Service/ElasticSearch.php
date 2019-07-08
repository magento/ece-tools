<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Service;

use Magento\MagentoCloud\Config\Environment;
use Magento\MagentoCloud\Http\ClientFactory;
use Psr\Log\LoggerInterface;

/**
 * Returns ElasticSearch service configurations.
 */
class ElasticSearch implements ServiceInterface
{
    const RELATIONSHIP_KEY = 'elasticsearch';
    const ENGINE_NAME = 'elasticsearch';

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
     */
    public function getVersion(): string
    {
        if ($this->version === null) {
            $this->version = '0';

            $config = $this->getConfiguration();
            if (!$config) {
                return $this->version;
            }

            try {
                $esConfiguration = $this->call(sprintf(
                    '%s:%s',
                    $config['host'],
                    $config['port']
                ));

                $this->version = $esConfiguration['version']['number'];
            } catch (\Exception $exception) {
                $this->logger->warning('Can\'t get version of elasticsearch: ' . $exception->getMessage());
            }
        }

        return $this->version;
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
                '%s:%s/_template',
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
