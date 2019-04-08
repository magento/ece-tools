<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Process\Deploy\InstallUpdate\ConfigUpdate\SearchEngine;

use Magento\MagentoCloud\Config\ConfigMerger;
use Magento\MagentoCloud\Config\Environment;
use Magento\MagentoCloud\Config\Stage\DeployInterface;
use Magento\MagentoCloud\Package\Manager;

/**
 * Returns version of elasticsearch
 */
class ElasticSuite
{
    /**
     * @var Manager
     */
    private $manager;

    /**
     * @var DeployInterface
     */
    private $stageConfig;

    /**
     * @var ConfigMerger
     */
    private $configMerger;

    /**
     * @var Environment
     */
    private $environment;

    /**
     * @var ElasticSearch
     */
    private $elasticSearch;

    /**
     * @param Manager $manager
     * @param DeployInterface $stageConfig
     * @param ConfigMerger $configMerger
     * @param Environment $environment
     * @param ElasticSearch $elasticSearch
     */
    public function __construct(
        Manager $manager,
        DeployInterface $stageConfig,
        ConfigMerger $configMerger,
        Environment $environment,
        ElasticSearch $elasticSearch
    ) {
        $this->manager = $manager;
        $this->stageConfig = $stageConfig;
        $this->configMerger = $configMerger;
        $this->environment = $environment;
        $this->elasticSearch = $elasticSearch;
    }

    /**
     * Returns search engine configuration. At least contains 'engine' option.
     *
     * @return array
     */
    public function get(): array
    {
        $envConfig = (array)$this->stageConfig->get(DeployInterface::VAR_ELASTIC_SUITE_CONFIGURATION);

        return $this->configMerger->mergeConfigs($this->getSearchConfig(), $envConfig);
    }

    /**
     * @return bool
     */
    public function isInstalled(): bool
    {
        return $this->manager->has('smile/elasticsuite');
    }

    /**
     * @return array
     */
    private function getSearchConfig(): array
    {
        $esConfig = $this->environment->getRelationship('elasticsearch')[0] ?? [];

        if (!isset($esConfig['host'], $esConfig['port'])) {
            return [];
        }

        $config = [
            'es_client' => [
                'servers' => $esConfig['host'] . ':' . $esConfig['port']
            ]
        ];

        $template = $this->elasticSearch->getTemplate();

        if (isset($template['index']['number_of_shards'])) {
            $config['indices_settings']['number_of_shards'] = (int)$template['index']['number_of_shards'];
        }

        if (isset($template['index']['number_of_replicas'])) {
            $config['indices_settings']['number_of_replicas'] = (int)$template['index']['number_of_replicas'];
        }

        if (isset($esConfig['query']['index'])) {
            $config['es_client']['indices_alias'] = $esConfig['query']['index'];
        }

        return $config;
    }
}
