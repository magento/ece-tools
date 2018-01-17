<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Process\Deploy\InstallUpdate\ConfigUpdate\Session;

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
     * @param Environment $environment
     * @param DeployInterface $stageConfig
     */
    public function __construct(
        Environment $environment,
        DeployInterface $stageConfig
    ) {
        $this->environment = $environment;
        $this->stageConfig = $stageConfig;
    }

    /**
     * Returns session configuration.
     *
     * If session configuration sets in SESSION_CONFIGURATION variable return it, otherwise checks if exists redis
     * configuration in relationships and if so, makes session configuration for redis.
     * Returns an empty array in other case.
     *
     * @return array
     */
    public function get(): array
    {
        $envSessionConfiguration = (array)$this->stageConfig->get(DeployInterface::VAR_SESSION_CONFIGURATION);

        if ($this->isSessionConfigurationValid($envSessionConfiguration)) {
            return $envSessionConfiguration;
        }

        $redisConfig = $this->environment->getRelationship('redis');

        if (!count($redisConfig)) {
            return [];
        }

        return [
            'save' => 'redis',
            'redis' => [
                'host' => $redisConfig[0]['host'],
                'port' => $redisConfig[0]['port'],
                'database' => 0,
                'disable_locking' => (int)$this->isLockingDisabled(),
            ]
        ];
    }

    /**
     * Checks if disable_locking options is enabled.
     * By default this method returns true and disable_locking options will be set to 1.
     * For turning this option off environment variable 'REDIS_SESSION_DISABLE_LOCKING' should have value 'disabled'.
     *
     * @return bool
     */
    private function isLockingDisabled(): bool
    {
        return $this->stageConfig->get(DeployInterface::VAR_REDIS_SESSION_DISABLE_LOCKING);
    }

    /**
     * Checks that given session configuration is valid.
     *
     * @param array $sessionConfiguration
     * @return bool
     */
    private function isSessionConfigurationValid(array $sessionConfiguration): bool
    {
        return !empty($sessionConfiguration) && isset($sessionConfiguration['save']);
    }
}
