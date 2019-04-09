<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Process\Deploy\InstallUpdate\ConfigUpdate\SearchEngine;

use Magento\MagentoCloud\Config\Environment;
use Magento\MagentoCloud\Http\ClientFactory;
use Psr\Log\LoggerInterface;

/**
 * Returns configurations from ElasticSearch.
 */
class ElasticSearch
{
    const RELATIONSHIP_KEY = 'elasticsearch';

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
     * @return bool
     */
    public function isInstalled(): bool
    {
        return (bool)$this->environment->getRelationship(self::RELATIONSHIP_KEY);
    }

    /**
     * @return array
     */
    public function getConfig(): array
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

            $relationships = $this->environment->getRelationships();
            if (!isset($relationships['elasticsearch'])) {
                return $this->version;
            }

            $esConfig = $relationships['elasticsearch'][0];

            try {
                $esConfiguration = $this->call(sprintf(
                    '%s:%s',
                    $esConfig['host'],
                    $esConfig['port']
                ));

                $this->version = $esConfiguration['version']['number'];
            } catch (\Exception $exception) {
                $this->logger->warning('Can\'t get version of elasticsearch: ' . $exception->getMessage());
            }
        }

        return $this->version;
    }

    /**
     * @return array
     */
    public function getTemplate(): array
    {
        $config = $this->getConfig();

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
