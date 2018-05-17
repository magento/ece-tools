<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Process\Deploy\InstallUpdate\ConfigUpdate\Db;

use Magento\MagentoCloud\Config\ConfigMerger;
use Magento\MagentoCloud\Config\Environment;
use Magento\MagentoCloud\Config\Stage\DeployInterface;
use Psr\Log\LoggerInterface;

/**
 * Returns database configuration.
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
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var SlaveConfig
     */
    private $slaveConfig;

    /**
     * @var ConfigMerger
     */
    private $configMerger;

    /**
     * @param Environment $environment
     * @param SlaveConfig $slaveConfig
     * @param DeployInterface $stageConfig
     * @param LoggerInterface $logger
     * @param ConfigMerger $configMerger
     */
    public function __construct(
        Environment $environment,
        SlaveConfig $slaveConfig,
        DeployInterface $stageConfig,
        LoggerInterface $logger,
        ConfigMerger $configMerger
    ) {
        $this->environment = $environment;
        $this->slaveConfig = $slaveConfig;
        $this->stageConfig = $stageConfig;
        $this->logger = $logger;
        $this->configMerger = $configMerger;
    }

    /**
     * Returns database configuration.
     *
     * @return array
     */
    public function get(): array
    {
        $envDbConfig = $this->stageConfig->get(DeployInterface::VAR_DATABASE_CONFIGURATION);

        if (!$this->configMerger->isEmpty($envDbConfig) && !$this->configMerger->isMergeRequired($envDbConfig)) {
            return $this->configMerger->clear($envDbConfig);
        }

        $mainConnectionData = [
            'username' => $this->environment->getDbUser(),
            'host' => $this->environment->getDbHost(),
            'dbname' => $this->environment->getDbName(),
            'password' => $this->environment->getDbPassword(),
        ];

        $dbConfig = [
            'connection' => [
                'default' => $mainConnectionData,
                'indexer' => $mainConnectionData,
            ],
        ];

        if ($this->stageConfig->get(DeployInterface::VAR_MYSQL_USE_SLAVE_CONNECTION)) {
            if (!$this->isDbConfigurationCompatible($envDbConfig)) {
                $this->logger->warning(
                    'You have changed db configuration that not compatible with default slave connection.'
                );
            } else {
                $slaveConfiguration = $this->slaveConfig->get();

                if (!empty($slaveConfiguration)) {
                    $this->logger->info('Set DB slave connection.');
                    $dbConfig['slave_connection']['default'] = $slaveConfiguration;
                }
            }
        }

        return $this->configMerger->mergeConfigs($dbConfig, $envDbConfig);
    }

    /**
     * Checks that database configuration was changed in DATABASE_CONFIGURATION variable
     * in not compatible way with slave_connection.
     *
     * Returns true if $envDbConfig contains host or dbname for default connection
     * that doesn't match connection from relationships,
     * otherwise return false.
     *
     * @param array $envDbConfig
     * @return boolean
     */
    private function isDbConfigurationCompatible(array $envDbConfig)
    {
        if ((isset($envDbConfig['connection']['default']['host'])
                && $envDbConfig['connection']['default']['host'] !== $this->environment->getDbHost())
            || (isset($envDbConfig['connection']['default']['dbname'])
                && $envDbConfig['connection']['default']['dbname'] !== $this->environment->getDbName())
        ) {
            return false;
        }

        return true;
    }
}
