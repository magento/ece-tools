<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Process\Deploy\InstallUpdate\ConfigUpdate\SearchEngine;

use Composer\Semver\Semver;
use Magento\MagentoCloud\Config\ConfigMerger;
use Magento\MagentoCloud\Config\Environment;
use Magento\MagentoCloud\Config\Stage\DeployInterface;
use Magento\MagentoCloud\Package\MagentoVersion;

/**
 * Returns search configuration.
 */
class Config
{
    /**
     * @var Environment
     */
    private $environment;

    /**
     * @var DeployInterface
     */
    private $stageConfig;

    /**
     * @var ElasticSearch
     */
    private $elasticSearch;

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
     * @param ElasticSearch $elasticSearch
     * @param MagentoVersion $version
     * @param ConfigMerger $configMerger
     */
    public function __construct(
        Environment $environment,
        DeployInterface $stageConfig,
        ElasticSearch $elasticSearch,
        MagentoVersion $version,
        ConfigMerger $configMerger
    ) {
        $this->environment = $environment;
        $this->stageConfig = $stageConfig;
        $this->elasticSearch = $elasticSearch;
        $this->magentoVersion = $version;
        $this->configMerger = $configMerger;
    }

    /**
     * Returns search engine configuration. At least contains 'engine' option.
     *
     * @return array
     */
    public function get(): array
    {
        $envSearchConfig = (array)$this->stageConfig->get(DeployInterface::VAR_SEARCH_CONFIGURATION);

        if ($this->isSearchConfigValid($envSearchConfig)
            && !$this->configMerger->isMergeRequired($envSearchConfig)
        ) {
            return $this->configMerger->clear($envSearchConfig);
        }

        return $this->configMerger->mergeConfigs($this->getSearchConfig(), $envSearchConfig);
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

        if (Semver::satisfies($this->elasticSearch->getVersion(), '>= 5')) {
            $engine = 'elasticsearch5';
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
