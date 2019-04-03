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
    const NUMBER_OF_SHARDS = 3;
    const NUMBER_OF_REPLICAS = 2;

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
     * @param Manager $manager
     * @param DeployInterface $stageConfig
     * @param ConfigMerger $configMerger
     * @param Environment $environment
     */
    public function __construct(
        Manager $manager,
        DeployInterface $stageConfig,
        ConfigMerger $configMerger,
        Environment $environment
    ) {
        $this->manager = $manager;
        $this->stageConfig = $stageConfig;
        $this->configMerger = $configMerger;
        $this->environment = $environment;
    }

    /**
     * Returns search engine configuration. At least contains 'engine' option.
     *
     * @return array
     */
    public function get(): array
    {
        $envConfig = (array)$this->stageConfig->get(DeployInterface::VAR_ELASTIC_SUITE_CONFIGURATION);

        if ($this->isSearchConfigValid($envConfig)
            && !$this->configMerger->isMergeRequired($envConfig)
        ) {
            return $this->configMerger->clear($envConfig);
        }

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
     * @param array $config
     * @return bool
     */
    private function isSearchConfigValid(array $config): bool
    {
        return array_key_exists('es_client', $config);
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
            ],
            'indices_settings' => [
                'number_of_shards' => self::NUMBER_OF_SHARDS,
                'number_of_replicas' => self::NUMBER_OF_REPLICAS
            ]
        ];

        if (isset($esConfig['query']['index'])) {
            $config['es_client']['indices_alias'] = $esConfig['query']['index'];
        }

        return $config;
    }
}
