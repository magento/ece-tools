<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Config;

use Magento\MagentoCloud\Config\SearchEngine\ElasticSuite;
use Magento\MagentoCloud\Config\Stage\DeployInterface;
use Magento\MagentoCloud\Package\MagentoVersion;
use Magento\MagentoCloud\Package\UndefinedPackageException;
use Magento\MagentoCloud\Service\ElasticSearch;
use Magento\MagentoCloud\Service\OpenSearch;
use Magento\MagentoCloud\Service\ServiceException;
use Magento\MagentoCloud\Service\Search\AbstractService as AbstractSearchService;

/**
 * Returns search configuration.
 */
class SearchEngine
{
    public const ENGINE_MYSQL = 'mysql';

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
     * @var OpenSearch
     */
    private $openSearch;

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
     * @param OpenSearch $openSearch
     * @param ElasticSuite $elasticSuite
     * @param MagentoVersion $version
     * @param ConfigMerger $configMerger
     */
    public function __construct(
        Environment $environment,
        DeployInterface $stageConfig,
        ElasticSearch $elasticSearch,
        OpenSearch $openSearch,
        ElasticSuite $elasticSuite,
        MagentoVersion $version,
        ConfigMerger $configMerger
    ) {
        $this->environment = $environment;
        $this->stageConfig = $stageConfig;
        $this->elasticSearch = $elasticSearch;
        $this->openSearch = $openSearch;
        $this->elasticSuite = $elasticSuite;
        $this->magentoVersion = $version;
        $this->configMerger = $configMerger;
    }

    /**
     * Returns search engine configuration. At least contains 'engine' option.
     *
     * @return array
     * @throws ServiceException
     */
    public function getConfig(): array
    {
        if ($this->config === null) {
            $resolveMerge = function () {
                $searchConfig = (array)$this->stageConfig->get(DeployInterface::VAR_SEARCH_CONFIGURATION);

                if (isset($searchConfig['engine']) && !$this->configMerger->isMergeRequired($searchConfig)) {
                    return $this->configMerger->clear($searchConfig);
                }

                return $this->configMerger->merge(
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
     * @throws ServiceException
     */
    public function getName(): string
    {
        return $this->getConfig()['system']['default']['catalog']['search']['engine'];
    }

    /**
     * Checks if search engine is a prt of ElasticSearch family (i.e. ElasticSuite).
     *
     * @return bool
     * @throws ServiceException
     */
    public function isESFamily(): bool
    {
        $searchEngine = $this->getName();

        return (strpos($searchEngine, ElasticSearch::ENGINE_NAME) === 0)
            || ($searchEngine === ElasticSuite::ENGINE_NAME) || ($searchEngine === OpenSearch::ENGINE_NAME);
    }

    /**
     * @return array
     *
     * @throws UndefinedPackageException
     * @throws ServiceException
     */
    private function getSearchConfig(): array
    {
        if ($this->openSearch->getConfiguration()) {
            return $this->getElasticSearchFamilyConfiguration($this->openSearch);
        }

        if ($this->elasticSearch->getConfiguration()) {
            return $this->getElasticSearchFamilyConfiguration($this->elasticSearch);
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
     * @param AbstractSearchService $searchService
     * @return array
     *
     * @throws ServiceException
     */
    private function getElasticSearchFamilyConfiguration(AbstractSearchService $searchService): array
    {
        $engine = $searchService->getFullEngineName();
        $config = $searchService->getConfiguration();

        $elasticSearchConfig = [
            'engine' => $engine,
            "{$engine}_server_hostname" => $searchService->getHost(),
            "{$engine}_server_port" => $config['port'],
        ];

        if ($searchService->isAuthEnabled()) {
            $elasticSearchConfig["{$engine}_enable_auth"] = 1;
            $elasticSearchConfig["{$engine}_username"] = $config['username'];
            $elasticSearchConfig["{$engine}_password"] = $config['password'];
        }

        if (isset($config['query']['index'])) {
            $elasticSearchConfig["{$engine}_index_prefix"] = $config['query']['index'];
        }

        return $elasticSearchConfig;
    }
}
