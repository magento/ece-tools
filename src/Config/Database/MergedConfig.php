<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Config\Database;

use Magento\MagentoCloud\Config\ConfigMerger;
use Magento\MagentoCloud\Config\Stage\DeployInterface;
use Magento\MagentoCloud\DB\Data\ConnectionInterface;
use Magento\MagentoCloud\Config\Deploy\Reader as ConfigReader;
use Psr\Log\LoggerInterface;

/**
 * Returns merged final database configuration.
 */
class MergedConfig implements ConfigInterface
{
    /**
     * @var DeployInterface
     */
    private $stageConfig;

    /**
     * @var SlaveConfig
     */
    private $slaveConfig;

    /**
     * @var ConfigMerger
     */
    private $configMerger;

    /**
     * @var ConnectionInterface
     */
    private $connectionData;

    /**
     * @var ConfigReader
     */
    private $configReader;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * Final database configuration after merging.
     *
     * @var array
     */
    private $mergedConfig;

    /**
     * @param ConnectionInterface $connectionData
     * @param ConfigReader $configReader
     * @param SlaveConfig $slaveConfig
     * @param DeployInterface $stageConfig
     * @param LoggerInterface $logger
     * @param ConfigMerger $configMerger
     */
    public function __construct(
        ConnectionInterface $connectionData,
        ConfigReader $configReader,
        SlaveConfig $slaveConfig,
        DeployInterface $stageConfig,
        LoggerInterface $logger,
        ConfigMerger $configMerger
    ) {
        $this->connectionData = $connectionData;
        $this->configReader = $configReader;
        $this->slaveConfig = $slaveConfig;
        $this->stageConfig = $stageConfig;
        $this->configMerger = $configMerger;
        $this->logger = $logger;
    }

    /**
     * Returns database configuration.
     *
     * @return array
     */
    public function get(): array
    {
        if ($this->mergedConfig !== null) {
            return $this->mergedConfig;
        }

        $envDbConfig = $this->stageConfig->get(DeployInterface::VAR_DATABASE_CONFIGURATION);

        if (!$this->configMerger->isEmpty($envDbConfig) && !$this->configMerger->isMergeRequired($envDbConfig)) {
            return $this->configMerger->clear($envDbConfig);
        }

        if (!empty($this->connectionData->getHost())) {
            $dbConfig = $this->generateDbConfig($envDbConfig);
        } else {
            $dbConfig = $this->getDbConfigFromEnvFile();
        }

        $this->mergedConfig = $this->configMerger->mergeConfigs($dbConfig, $envDbConfig);

        return $this->mergedConfig;
    }

    /**
     * Generates database configuration from environment relationships.
     *
     * @param array envDbConfig
     * @return array
     */
    private function generateDbConfig(array $envDbConfig): array
    {
        $connectionData = [
            'username' => $this->connectionData->getUser(),
            'host' => $this->connectionData->getHost(),
            'dbname' => $this->connectionData->getDbName(),
            'password' => $this->connectionData->getPassword(),
        ];

        $dbConfig = [
            'connection' => [
                'default' => $connectionData,
                'indexer' => $connectionData,
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
                } else {
                    $this->logger->info('Slave connection is not configured.');
                }
            }
        }

        return $dbConfig;
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
                && $envDbConfig['connection']['default']['host'] !== $this->connectionData->getHost())
            || (isset($envDbConfig['connection']['default']['dbname'])
                && $envDbConfig['connection']['default']['dbname'] !== $this->connectionData->getDbName())
        ) {
            return false;
        }

        return true;
    }

    /**
     * Returns db configuration from env.php.
     *
     * This method is calling only in case when database relationship configuration doesn't exist and
     * database is not configured through .magento.env.yaml or env variable.
     * It's workaround for scenarios when magento was installed by raw setup:install command not by deploy scripts.
     */
    private function getDbConfigFromEnvFile(): array
    {
        return $this->configReader->read()['db'] ?? [];
    }
}
