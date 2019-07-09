<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Config;

use Composer\Semver\Semver;
use Magento\MagentoCloud\Config\SearchEngine\ElasticSuite;
use Magento\MagentoCloud\Config\Stage\DeployInterface;
use Magento\MagentoCloud\Package\MagentoVersion;
use Magento\MagentoCloud\Package\UndefinedPackageException;
use Magento\MagentoCloud\Service\ElasticSearch;

/**
 * Returns search configuration.
 */
class SearchEngine
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
     * @var ElasticSuite
     */
    private $elasticSuite;

    /**
     * @var array
     */
    private $config;

    /**
     * @param Environment $environment
     * @param DeployInterface $stageConfig
     * @param ElasticSearch $elasticSearch
     * @param ElasticSuite $elasticSuite
     * @param MagentoVersion $version
     * @param ConfigMerger $configMerger
     */
    public function __construct(
        Environment $environment,
        DeployInterface $stageConfig,
        ElasticSearch $elasticSearch,
        ElasticSuite $elasticSuite,
        MagentoVersion $version,
        ConfigMerger $configMerger
    ) {
        $this->environment = $environment;
        $this->stageConfig = $stageConfig;
        $this->elasticSearch = $elasticSearch;
        $this->elasticSuite = $elasticSuite;
        $this->magentoVersion = $version;
        $this->configMerger = $configMerger;
    }

    /**
     * Returns search engine configuration. At least contains 'engine' option.
     *
     * @return array
     *
     * @throws UndefinedPackageException
     */
    public function getConfig(): array
    {
        if ($this->config === null) {
            $resolveMerge = function () {
                $searchConfig = (array)$this->stageConfig->get(DeployInterface::VAR_SEARCH_CONFIGURATION);

                if (isset($searchConfig['engine']) && !$this->configMerger->isMergeRequired($searchConfig)) {
                    return $this->configMerger->clear($searchConfig);
                }

                return $this->configMerger->mergeConfigs(
                    $this->getSearchConfig(),
                    $searchConfig
                );
            };

            $this->config['system']['default']['catalog']['search'] = $resolveMerge();

            if ($this->elasticSuite->isInstalled()) {
                $this->config['system']['default']['smile_elasticsuite_core_base_settings'] =
                    $this->elasticSuite->get();
                $this->config['system']['default']['catalog']['search']['engine'] = ElasticSuite::ENGINE_NAME;
            }
        }

        return $this->config;
    }

    /**
     * @return string
     *
     * @throws UndefinedPackageException
     */
    public function getName(): string
    {
        return $this->getConfig()['system']['default']['catalog']['search']['engine'];
    }

    /**
     * Checks if search engine is a prt of ElasticSearch family (i.e. ElasticSuite).
     *
     * @return bool
     */
    public function isESFamily(): bool
    {
        $searchEngine = $this->getName();

        return (strpos($searchEngine, ElasticSearch::ENGINE_NAME) === 0)
            || ($searchEngine === ElasticSuite::ENGINE_NAME);
    }

    /**
     * @return array
     *
     * @throws UndefinedPackageException
     */
    private function getSearchConfig(): array
    {
        if ($esConfig = $this->elasticSearch->getConfiguration()) {
            return $this->getElasticSearchConfiguration($esConfig);
        }

        $solrConfig = $this->environment->getRelationship('solr');

        if ($solrConfig && $this->magentoVersion->satisfies('<2.2')) {
            return $this->getSolrConfiguration($solrConfig[0]);
        }

        return ['engine' => 'mysql'];
    }

    /**
     * Returns SOLR configuration
     *
     * @param array $config Solr connection configuration
     * @return array
     */
    private function getSolrConfiguration(array $config): array
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
    private function getElasticSearchConfiguration(array $config): array
    {
        $engine = ElasticSearch::ENGINE_NAME;

        $esVersion = $this->elasticSearch->getVersion();
        if (Semver::satisfies($esVersion, '>= 5')) {
            $engine .= (int)$esVersion;
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
}
