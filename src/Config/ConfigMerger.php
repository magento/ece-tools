<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

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
     * @param array $customConfig
     * @return array
     */
    public function merge(array $baseConfig, array $customConfig): array
    {
        if ($this->isMergeRequired($customConfig)) {
            return array_replace_recursive($baseConfig, $this->clear($customConfig));
        }

        return $baseConfig;
    }

    /**
     * Merges two configs according to rules.
     *
     * 1. If merge is required - merge configs
     * 2. If specific key is required in $customConfig and present - merge configs
     * 3. If no specific key is required and $customConfig not empty - merge configs
     * 4. Otherwise return $baseConfig
     *
     * @param array $baseConfig
     * @param array $customConfig
     * @param string|null $key
     * @return array
     */
    public function mergeIf(array $baseConfig, array $customConfig, string $key = null): array
    {
        $cleanedCustomConfig = $this->clear($customConfig);
        $isMergeRequired = $this->isMergeRequired($customConfig);

        if ($isMergeRequired) {
            return $this->merge($baseConfig, $customConfig);
        }

        if ($key !== null && isset($cleanedCustomConfig[$key])) {
            return $cleanedCustomConfig;
        }

        if ($key === null && $cleanedCustomConfig) {
            return $cleanedCustomConfig;
        }

        return $baseConfig;
    }
}
