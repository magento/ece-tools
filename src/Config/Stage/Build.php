<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Config\Stage;

use Magento\MagentoCloud\Config\StageConfigInterface;
use Magento\MagentoCloud\Config\Environment\Reader as EnvironmentReader;
use Magento\MagentoCloud\Config\Build\Reader as BuildReader;

/**
 * @inheritdoc
 */
class Build implements StageConfigInterface
{
    /**
     * @var EnvironmentReader
     */
    private $environmentReader;

    /**
     * @var BuildReader
     */
    private $buildReader;

    /**
     * @var array
     */
    private $mergedConfig;

    /**
     * @param EnvironmentReader $environmentReader
     * @param BuildReader $buildReader
     */
    public function __construct(
        EnvironmentReader $environmentReader,
        BuildReader $buildReader
    ) {
        $this->environmentReader = $environmentReader;
        $this->buildReader = $buildReader;
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
                $envConfig[self::STAGE_GLOBAL] ?? [],
                $envConfig[self::STAGE_BUILD] ?? [],
                $this->buildReader->read()
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
            self::VAR_SKIP_SCD => false,
            self::VAR_SCD_COMPRESSION_LEVEL => 6,
        ];
    }
}
