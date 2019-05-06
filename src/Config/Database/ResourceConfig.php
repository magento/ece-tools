<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Config\Database;

use Magento\MagentoCloud\Config\Stage\DeployInterface;
use Magento\MagentoCloud\Config\ConfigMerger;

/**
 * Returns merged final resources configuration.
 */
class ResourceConfig implements ConfigInterface
{
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
     * Final resource configuration after merging
     *
     * @var array
     */
    private $mergedConfig;

    /**
     * Default resource configuration
     *
     * @var array
     */
    private $rsConfig = [
        'default_setup' => [
            'connection' => 'default',
        ],
    ];

    /**
     * @param DeployInterface $stageConfig
     * @param ConfigMerger $configMerger
     */
    public function __construct(DeployInterface $stageConfig, ConfigMerger $configMerger)
    {
        $this->stageConfig = $stageConfig;
        $this->configMerger = $configMerger;
    }

    /**
     * Returns resources configuration.
     *
     * @return array
     */
    public function get(): array
    {
        if ($this->mergedConfig !== null) {
            return $this->mergedConfig;
        }

        $envRsConfig = $this->stageConfig->get(DeployInterface::VAR_RESOURCE_CONFIGURATION);

        if (!$this->configMerger->isEmpty($envRsConfig) && !$this->configMerger->isMergeRequired($envRsConfig)) {
            return $this->mergedConfig = $this->configMerger->clear($envRsConfig);
        }

        return $this->mergedConfig = $this->configMerger->mergeConfigs($this->rsConfig, $envRsConfig);
    }
}
