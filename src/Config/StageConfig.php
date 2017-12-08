<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Config;

use Magento\MagentoCloud\Config\Environment\Reader as EnvironmentReader;
use Magento\MagentoCloud\Config\Environment as EnvironmentConfig;
use Magento\MagentoCloud\Config\Build as BuildConfig;

/**
 * @inheritdoc
 */
class StageConfig implements StageConfigInterface
{
    /**
     * Default, unified stage.
     */
    const STAGE_GLOBAL = 'global';

    /**
     * @var EnvironmentReader
     */
    private $environmentReader;

    /**
     * @var EnvironmentConfig
     */
    private $environmentConfig;

    /**
     * @var BuildConfig
     */
    private $buildConfig;

    /**
     * @var array
     */
    private $mergedConfig;

    /**
     * @param EnvironmentReader $environmentReader
     * @param Environment $environmentConfig
     * @param Build $buildConfig
     */
    public function __construct(
        EnvironmentReader $environmentReader,
        EnvironmentConfig $environmentConfig,
        BuildConfig $buildConfig
    ) {
        $this->environmentReader = $environmentReader;
        $this->environmentConfig = $environmentConfig;
        $this->buildConfig = $buildConfig;
    }

    /**
     * {@inheritdoc}
     *
     * @throws \Magento\MagentoCloud\Filesystem\FileSystemException
     * @throws \RuntimeException
     */
    public function get(string $stage, string $name)
    {
        if ($stage === self::STAGE_BUILD && $this->buildConfig->get($name) !== null) {
            return $this->buildConfig->get($name);
        } elseif ($stage === self::STAGE_DEPLOY && $this->environmentConfig->getVariable($name) !== null) {
            return $this->environmentConfig->getVariable($name);
        }

        $config = $this->mergeConfig();

        if (isset($config[$stage][$name])) {
            return $config[$stage][$name];
        } elseif (isset($config[self::STAGE_GLOBAL][$name])) {
            return $config[self::STAGE_GLOBAL][$name];
        }

        throw new \RuntimeException(sprintf(
            'Default config value for %s:%s was not provided',
            $stage,
            $name
        ));
    }

    /**
     * @return array
     * @throws \Magento\MagentoCloud\Filesystem\FileSystemException
     */
    private function mergeConfig(): array
    {
        if (null === $this->mergedConfig) {
            $this->mergedConfig = array_replace_recursive(
                $this->getDefault(),
                $this->environmentReader->read()
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
            self::STAGE_GLOBAL => [
                self::VAR_SCD_STRATEGY => '',
            ],
            self::STAGE_BUILD => [
                self::VAR_SKIP_SCD => false,
                self::VAR_SCD_COMPRESSION_LEVEL => 6,
            ],
            self::STAGE_DEPLOY => [
                self::VAR_SCD_COMPRESSION_LEVEL => 4,
            ],
        ];
    }

    /**
     * @inheritdoc
     */
    public function getBuild(string $name)
    {
        return $this->get(self::STAGE_BUILD, $name);
    }

    /**
     * @inheritdoc
     */
    public function getDeploy(string $name)
    {
        return $this->get(self::STAGE_DEPLOY, $name);
    }
}
