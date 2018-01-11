<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Process\Deploy\InstallUpdate\ConfigUpdate;

use Magento\MagentoCloud\Config\Environment;
use Magento\MagentoCloud\Config\Stage\DeployInterface;
use Magento\MagentoCloud\Process\ProcessInterface;
use Magento\MagentoCloud\Config\Deploy\Reader as ConfigReader;
use Magento\MagentoCloud\Config\Deploy\Writer as ConfigWriter;
use Psr\Log\LoggerInterface;

/**
 * Processes configuration for session.
 */
class Session implements ProcessInterface
{
    /**
     * @var Environment
     */
    private $environment;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var ConfigWriter
     */
    private $configWriter;

    /**
     * @var ConfigReader
     */
    private $configReader;

    /**
     * @var DeployInterface
     */
    private $stageConfig;

    /**
     * @param Environment $environment
     * @param ConfigReader $configReader
     * @param ConfigWriter $configWriter
     * @param LoggerInterface $logger
     * @param DeployInterface $stageConfig
     */
    public function __construct(
        Environment $environment,
        ConfigReader $configReader,
        ConfigWriter $configWriter,
        LoggerInterface $logger,
        DeployInterface $stageConfig
    ) {
        $this->environment = $environment;
        $this->configReader = $configReader;
        $this->configWriter = $configWriter;
        $this->logger = $logger;
        $this->stageConfig = $stageConfig;
    }


    /**
     * Executes the process.
     *
     * @return void
     */
    public function execute()
    {
        $config = $this->configReader->read();

        $envSessionConfiguration = (array)$this->stageConfig->get(DeployInterface::VAR_SESSION_CONFIGURATION);
        if ($this->isSessionConfigurationValid($envSessionConfiguration)) {
            $config['session'] = $envSessionConfiguration;
        } elseif (count($redisConfig = $this->environment->getRelationship('redis'))) {
            $redisSessionConfig = [
                'host' => $redisConfig[0]['host'],
                'port' => $redisConfig[0]['port'],
                'database' => 0,
                'disable_locking' => (int)$this->isLockingDisabled(),
            ];
            $config['session'] = [
                'save' => 'redis',
                'redis' => array_replace_recursive(
                    $config['session']['redis'] ?? [],
                    $redisSessionConfig
                ),
            ];
        } else {
            $config = $this->removeRedisSessionConfiguration($config);
        }

        $this->configWriter->write($config);
    }


    /**
     * Clears session configuration from redis usages.
     *
     * @param array $config An array of application configuration
     * @return array
     */
    private function removeRedisSessionConfiguration($config)
    {
        $this->logger->info('Removing redis session configuration from env.php.');

        if (isset($config['session']['save']) && $config['session']['save'] == 'redis') {
            $config['session']['save'] = 'db';
            if (isset($config['session']['redis'])) {
                unset($config['session']['redis']);
            }
        }

        return $config;
    }

    /**
     * Checks that given redis configuration is valid.
     *
     * @param array $sessionConfiguration
     * @return bool
     */
    private function isSessionConfigurationValid(array $sessionConfiguration): bool
    {
        return !empty($sessionConfiguration) && isset($sessionConfiguration['save']);
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

}
