<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Process\Deploy\InstallUpdate\ConfigUpdate\Session;

use Magento\MagentoCloud\Config\ConfigMerger;
use Magento\MagentoCloud\Config\Environment;
use Magento\MagentoCloud\Config\Stage\DeployInterface;

/**
 * Returns session configuration.
 */
class Config
{
    /**
     * @var Environment
     */
    private $environment;

    /**
     * @var DeployInterface
     */
    private $stageConfig;

    /**
     * @var ConfigMerger
     */
    private $configMerger;

    /**
     * @param Environment $environment
     * @param DeployInterface $stageConfig
     * @param ConfigMerger $configMerger
     */
    public function __construct(
        Environment $environment,
        DeployInterface $stageConfig,
        ConfigMerger $configMerger
    ) {
        $this->environment = $environment;
        $this->stageConfig = $stageConfig;
        $this->configMerger = $configMerger;
    }

    /**
     * Returns session configuration.
     *
     * If session configuration sets in SESSION_CONFIGURATION variable without _merge option return it,
     * otherwise checks if exists redis configuration in relationships and if so, makes session configuration for redis.
     * Merge configuration from env variable is merging enabled.
     * Returns an empty array in other case.
     *
     * @return array
     */
    public function get(): array
    {
        $envSessionConfiguration = (array)$this->stageConfig->get(DeployInterface::VAR_SESSION_CONFIGURATION);

        if ($this->isSessionConfigurationValid($envSessionConfiguration)
            && !$this->configMerger->isMergeRequired($envSessionConfiguration)
        ) {
            return $envSessionConfiguration;
        }

        $redisConfig = $this->environment->getRelationship('redis');

        if (!count($redisConfig)) {
            return [];
        }

        return $this->configMerger->mergeConfigs([
            'save' => 'redis',
            'redis' => [
                'host' => $redisConfig[0]['host'],
                'port' => $redisConfig[0]['port'],
                'database' => 0
            ]
        ], $envSessionConfiguration);
    }

    /**
     * Checks that given session configuration is valid.
     *
     * @param array $sessionConfiguration
     * @return bool
     */
    private function isSessionConfigurationValid(array $sessionConfiguration): bool
    {
        return !$this->configMerger->isEmpty($sessionConfiguration) && isset($sessionConfiguration['save']);
    }
}
