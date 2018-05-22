<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Config;

/**
 * Helper class for configuration merging.
 */
class ConfigMerger
{
    /**
     * Checks that config contains not only options.
     *
     * For example ['_merge': true] is empty configuration.
     *
     * @param array $config
     * @return bool
     */
    public function isEmpty(array $config): bool
    {
        return empty(array_diff_key($config, [
            StageConfigInterface::OPTION_MERGE => 1,
        ]));
    }

    /**
     * Removes options from configuration.
     *
     * @param array $config
     * @return array
     */
    public function clear(array $config): array
    {
        unset($config[StageConfigInterface::OPTION_MERGE]);

        return $config;
    }

    /**
     * Checks if given configuration requires a merge.
     *
     * Return true if option '_merge' not empty.
     *
     * @param array $config
     * @return bool
     */
    public function isMergeRequired(array $config): bool
    {
        return !empty($config[StageConfigInterface::OPTION_MERGE]) && !empty($this->clear($config));
    }

    /**
     * Merge two configs if merging is required, otherwise return $baseConfig without changes.
     *
     * @param array $baseConfig
     * @param array $configToMerge
     * @return array
     */
    public function mergeConfigs(array $baseConfig, array $configToMerge): array
    {
        if ($this->isMergeRequired($configToMerge)) {
            return array_replace_recursive($baseConfig, $this->clear($configToMerge));
        }

        return $baseConfig;
    }
}
