<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Config\Database;

use Magento\MagentoCloud\Config\ConfigMerger;
use Magento\MagentoCloud\Config\Stage\DeployInterface;

/**
 * Returns resources configuration.
 */
class ResourceConfig implements ConfigInterface
{
    /**
     * Keys for the description of the resource configuration
     */
    const KEY_RESOURCE = 'resource';
    const KEY_CONNECTION = 'connection';

    /**
     * Names fo resources
     */
    const RESOURCE_CHECKOUT = 'checkout';
    const RESOURCE_SALES = 'sales';
    const RESOURCE_DEFAULT_SETUP = 'default_setup';

    /**
     * Resources map
     */
    const RESOURCE_MAP = [
        DbConfig::CONNECTION_DEFAULT => self::RESOURCE_DEFAULT_SETUP,
        DbConfig::CONNECTION_CHECKOUT => self::RESOURCE_CHECKOUT,
        DbConfig::CONNECTION_SALES => self::RESOURCE_SALES,
    ];

    /**
     * Returns merged final database configuration.
     *
     * @var DbConfig
     */
    private $dbConfig;

    /**
     * Final configuration for deploy phase
     *
     * @var DeployInterface
     */
    private $stageConfig;

    /**
     * Class for configuration merging
     *
     * @var ConfigMerger
     */
    private $configMerger;

    /**
     * Final database configuration after merging
     *
     * @var array
     */
    private $resourceConfig;

    /**
     * @param DbConfig $dbConfig
     * @param ConfigMerger $configMerger
     * @param DeployInterface $stageConfig
     */
    public function __construct(
        DbConfig $dbConfig,
        DeployInterface $stageConfig,
        ConfigMerger $configMerger
    ) {
        $this->dbConfig = $dbConfig;
        $this->stageConfig = $stageConfig;
        $this->configMerger = $configMerger;
    }

    /**
     * Returns resource configuration
     *
     * @return array
     */
    public function get(): array
    {
        if (null !== $this->resourceConfig) {
            return $this->resourceConfig;
        }

        $customConfig = $this->stageConfig->get(DeployInterface::VAR_RESOURCE_CONFIGURATION);

        /**
         * Ece-tools do not support custom configuration of a split database.
         */
        foreach (self::RESOURCE_MAP as $connectionName => $resourceName) {
            if (in_array($connectionName, DbConfig::SPLIT_CONNECTIONS)
                && isset($customConfig[$resourceName])) {
                unset($customConfig[$resourceName]);
            }
        }

        if (!$this->configMerger->isEmpty($customConfig) && !$this->configMerger->isMergeRequired($customConfig)) {
            return $this->configMerger->clear($customConfig);
        }

        return $this->resourceConfig = $this->configMerger->merge($this->createConfig(), $customConfig);
    }

    /**
     * Creates resource configuration
     *
     * @return array
     */
    private function createConfig(): array
    {
        $connections = array_keys($this->dbConfig->get()[DbConfig::KEY_CONNECTION] ?? []);
        $config = [];
        foreach ($connections as $connectionName) {
            if (isset(self::RESOURCE_MAP[$connectionName])) {
                $config[self::RESOURCE_MAP[$connectionName]][self::KEY_CONNECTION] = $connectionName;
            }
        }

        return $config;
    }
}
