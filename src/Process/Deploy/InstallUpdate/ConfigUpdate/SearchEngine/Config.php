<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Process\Deploy\InstallUpdate\ConfigUpdate\SearchEngine;

use Magento\MagentoCloud\Config\ConfigMerger;
use Magento\MagentoCloud\Config\Environment;
use Magento\MagentoCloud\Config\Stage\DeployInterface;
use Magento\MagentoCloud\Http\ClientFactory;
use Magento\MagentoCloud\Package\MagentoVersion;
use Psr\Log\LoggerInterface;

/**
 * Returns search configuration.
 */
class Config
{
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var Environment
     */
    private $environment;

    /**
     * @var DeployInterface
     */
    private $stageConfig;

    /**
     * @var ClientFactory
     */
    private $clientFactory;

    /**
     * @var MagentoVersion
     */
    private $magentoVersion;

    /**
     * @var ConfigMerger
     */
    private $configMerger;

    /**
     * @param Environment $environment
     * @param DeployInterface $stageConfig
     * @param ClientFactory $client
     * @param MagentoVersion $version
     * @param LoggerInterface $logger
     * @param ConfigMerger $configMerger
     */
    public function __construct(
        Environment $environment,
        DeployInterface $stageConfig,
        ClientFactory $client,
        MagentoVersion $version,
        LoggerInterface $logger,
        ConfigMerger $configMerger
    ) {
        $this->environment = $environment;
        $this->stageConfig = $stageConfig;
        $this->clientFactory = $client;
        $this->magentoVersion = $version;
        $this->logger = $logger;
        $this->configMerger = $configMerger;
    }

    /**
     * @return array
     */
    public function get(): array
    {
        $envSearchConfig = (array)$this->stageConfig->get(DeployInterface::VAR_SEARCH_CONFIGURATION);
        $envSearchConfigValidFlag = $this->isSearchConfigValid($envSearchConfig);

        if ($envSearchConfigValidFlag && !$this->configMerger->isMergeRequired($envSearchConfig)) {
            return $this->configMerger->clear($envSearchConfig);
        }

        $searchConfig = $this->getSearchConfig();

        if ($envSearchConfigValidFlag && $this->configMerger->isMergeRequired($envSearchConfig)) {
            return $this->configMerger->mergeConfigs($searchConfig, $envSearchConfig);
        }

        return $searchConfig;
    }

    /**
     * @return array
     */
    private function getSearchConfig()
    {
        $relationships = $this->environment->getRelationships();
        $searchConfig = ['engine' => 'mysql'];

        if (isset($relationships['elasticsearch'])) {
            $searchConfig = $this->getElasticSearchConfiguration($relationships['elasticsearch'][0]);
        } elseif (isset($relationships['solr']) && $this->magentoVersion->satisfies('<2.2')) {
            $searchConfig = $this->getSolrConfiguration($relationships['solr'][0]);
        }

        return $searchConfig;
    }

    /**
     * Returns SOLR configuration
     *
     * @param array $config Solr connection configuration
     * @return array
     */
    private function getSolrConfiguration(array $config)
    {
        return [
            'engine' => 'solr',
            'solr_server_hostname' => $config['host'],
            'solr_server_port' => $config['port'],
            'solr_server_username' => $config['scheme'],
            'solr_server_path' => $config['path'],
        ];
    }

    /**
     * Returns ElasticSearch configuration
     *
     * @param array $config Elasticsearch connection configuration
     * @return array
     */
    private function getElasticSearchConfiguration(array $config)
    {
        $engine = 'elasticsearch';

        try {
            $response = $this->clientFactory->create()->get(sprintf(
                '%s:%s',
                $config['host'],
                $config['port']
            ));
            $esConfiguration = $response->getBody()->getContents();
            $esConfiguration = json_decode($esConfiguration, true);

            if (isset($esConfiguration['version']['number']) && $esConfiguration['version']['number'] >= 5) {
                $engine = 'elasticsearch5';
            }
        } catch (\Exception $exception) {
            $this->logger->warning($exception->getMessage());
        }

        $elasticSearchConfig = [
            'engine' => $engine,
            "{$engine}_server_hostname" => $config['host'],
            "{$engine}_server_port" => $config['port'],
        ];

        if (isset($config['query']['index'])) {
            $elasticSearchConfig["{$engine}_index_prefix"] = $config['query']['index'];
        }

        return $elasticSearchConfig;
    }

    /**
     * Checks that given configuration is valid.
     *
     * @param array $searchConfiguration
     * @return bool
     */
    private function isSearchConfigValid(array $searchConfiguration): bool
    {
        return isset($searchConfiguration['engine']);
    }
}
