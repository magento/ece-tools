<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Config\SearchEngine;

use Magento\MagentoCloud\Config\ConfigMerger;
use Magento\MagentoCloud\Config\Stage\DeployInterface;
use Magento\MagentoCloud\Package\Manager;
use Magento\MagentoCloud\Service\ElasticSearch;

/**
 * Provides an access to ElasticSuite configuration.
 */
class ElasticSuite
{
    const ENGINE_NAME = 'elasticsuite';

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
     * @var ElasticSearch
     */
    private $elasticSearch;

    /**
     * @param Manager $manager
     * @param DeployInterface $stageConfig
     * @param ConfigMerger $configMerger
     * @param ElasticSearch $elasticSearch
     */
    public function __construct(
        Manager $manager,
        DeployInterface $stageConfig,
        ConfigMerger $configMerger,
        ElasticSearch $elasticSearch
    ) {
        $this->manager = $manager;
        $this->stageConfig = $stageConfig;
        $this->configMerger = $configMerger;
        $this->elasticSearch = $elasticSearch;
    }

    /**
     * Returns search engine configuration.
     *
     * @return array
     */
    public function get(): array
    {
        $envConfig = (array)$this->stageConfig->get(DeployInterface::VAR_ELASTICSUITE_CONFIGURATION);

        return $this->configMerger->mergeConfigs($this->getConfig(), $envConfig);
    }

    /**
     * Checks if both ElasticSearch and ElasticSuite are installed.
     *
     * @return bool
     */
    public function isAvailable(): bool
    {
        return $this->elasticSearch->isInstalled() && $this->isInstalled();
    }

    /**
     * Checks if module is installed.
     *
     * @return bool
     */
    public function isInstalled(): bool
    {
        return $this->manager->has('smile/elasticsuite');
    }

    /**
     * Retrieves configuration including servers, shards and replicas.
     *
     * @return array
     */
    private function getConfig(): array
    {
        $esConfig = $this->elasticSearch->getConfiguration();

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
