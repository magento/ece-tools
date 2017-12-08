<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Config\Stage;

use Magento\MagentoCloud\Config\StageConfigInterface;
use Magento\MagentoCloud\Config\Environment\Reader as EnvironmentReader;
use Magento\MagentoCloud\Config\Environment as EnvironmentConfig;

/**
 * @inheritdoc
 */
class Deploy implements StageConfigInterface
{
    /**
     * @var EnvironmentReader
     */
    private $environmentReader;

    /**
     * @var EnvironmentConfig
     */
    private $environmentConfig;

    /**
     * @var array
     */
    private $mergedConfig;

    /**
     * @param EnvironmentReader $environmentReader
     * @param EnvironmentConfig $environmentConfig
     */
    public function __construct(EnvironmentReader $environmentReader, EnvironmentConfig $environmentConfig)
    {
        $this->environmentReader = $environmentReader;
        $this->environmentConfig = $environmentConfig;
    }

    /**
     * @inheritdoc
     */
    public function get(string $name, $default = null)
    {
        $config = $this->mergeConfig();

        return $config[$name] ?? $default;
    }

    /**
     * @return array
     */
    private function mergeConfig(): array
    {
        if (null === $this->mergedConfig) {
            $envConfig = $this->environmentReader->read();

            $this->mergedConfig = array_replace(
                $this->getDefault(),
                $envConfig[self::STAGE_GLOBAL] ?: [],
                $envConfig[self::STAGE_DEPLOY] ?: [],
                $this->environmentConfig->getVariables()
            );
        }

        return $this->mergedConfig;
    }

    /**
     * Resolves default configuration value if other was not provided.
     *
     * @return array
     */
    private function getDefault(): array
    {
        return [
            self::VAR_SCD_STRATEGY => '',
            self::VAR_SCD_COMPRESSION_LEVEL => 4,
        ];
    }
}
