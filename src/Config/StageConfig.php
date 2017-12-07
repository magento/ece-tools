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

        $config = $this->environmentReader->read();

        if (isset($config[$stage][$name])) {
            return $config[$stage][$name];
        } elseif (isset($config[self::STAGE_GLOBAL][$name])) {
            return $config[self::STAGE_GLOBAL][$name];
        }

        return $this->getDefault($stage, $name);
    }

    /**
     * Resolves default configuration value if other was not provided.
     *
     * @param string $stage
     * @param string $name
     * @return mixed
     * @throws \RuntimeException
     */
    private function getDefault(string $stage, string $name)
    {
        $default = [
            self::VAR_SCD_COMPRESSION_LEVEL => [
                self::STAGE_BUILD => 6,
                self::STAGE_DEPLOY => 4,
            ],
            self::VAR_SCD_STRATEGY => [
                self::STAGE_GLOBAL => '',
            ],
            self::VAR_SKIP_SCD => [
                self::STAGE_BUILD => false,
            ],
        ];

        if (isset($default[$name][$stage])) {
            return $default[$name][$stage];
        } elseif (isset($default[$name][self::STAGE_GLOBAL])) {
            return $default[$name][self::STAGE_GLOBAL];
        }

        throw new \RuntimeException(sprintf(
            'Default config value for %s:%s was not provided',
            $stage,
            $name
        ));
    }

    /**
     * {@inheritdoc}
     *
     * @throws \Magento\MagentoCloud\Filesystem\FileSystemException
     */
    public function getBuild(string $name)
    {
        return $this->get(self::STAGE_BUILD, $name);
    }

    /**
     * {@inheritdoc}
     *
     * @throws \Magento\MagentoCloud\Filesystem\FileSystemException
     */
    public function getDeploy(string $name)
    {
        return $this->get(self::STAGE_DEPLOY, $name);
    }
}
