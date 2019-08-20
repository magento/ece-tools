<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Config\Database;

use Magento\MagentoCloud\Config\ConfigMerger;
use Magento\MagentoCloud\Config\Deploy\Reader as ConfigReader;
use Magento\MagentoCloud\Config\Stage\DeployInterface;
use Magento\MagentoCloud\DB\Data\ConnectionInterface;
use Magento\MagentoCloud\DB\Data\RelationshipConnectionFactory;

/**
 * Returns merged final database configuration.
 */
class MergedConfig implements ConfigInterface
{
    /**
     * Final configuration for deploy phase
     *
     * @var DeployInterface
     */
    private $stageConfig;

    /**
     * Configuration for slave connection
     *
     * @var SlaveConfig
     */
    private $slaveConfig;

    /**
     * Class for configuration merging
     *
     * @var ConfigMerger
     */
    private $configMerger;

    /**
     * Connection data from relationship array
     *
     * @var ConnectionInterface
     */
    private $connectionData;

    /**
     * Reader for app/etc/env.php file
     *
     * @var ConfigReader
     */
    private $configReader;

    /**
     * Final database configuration after merging
     *
     * @var array
     */
    private $mergedConfig;

    /**
     * Factory for creation database configurations
     *
     * @var RelationshipConnectionFactory
     */
    private $connectionFactory;

    /**
     * @param RelationshipConnectionFactory $connectionFactory
     * @param ConfigReader $configReader
     * @param SlaveConfig $slaveConfig
     * @param DeployInterface $stageConfig
     * @param ConfigMerger $configMerger
     */
    public function __construct(
        RelationshipConnectionFactory $connectionFactory,
        ConfigReader $configReader,
        SlaveConfig $slaveConfig,
        DeployInterface $stageConfig,
        ConfigMerger $configMerger
    ) {
        $this->connectionFactory = $connectionFactory;
        $this->configReader = $configReader;
        $this->slaveConfig = $slaveConfig;
        $this->stageConfig = $stageConfig;
        $this->configMerger = $configMerger;
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
            return $this->mergedConfig = $this->configMerger->clear($envDbConfig);
        }

        if (!empty($this->getConnectionData()->getHost())) {
            $dbConfig = $this->generateDbConfig();
        } else {
            $dbConfig = $this->getDbConfigFromEnvFile();
        }

        return $this->mergedConfig = $this->configMerger->mergeConfigs($dbConfig, $envDbConfig);
    }

    /**
     * Generates database configuration from environment relationships.
     *
     * @return array
     */
    private function generateDbConfig(): array
    {
        $connectionData = [
            'username' => $this->getConnectionData()->getUser(),
            'host' => $this->getConnectionData()->getHost(),
            'dbname' => $this->getConnectionData()->getDbName(),
            'password' => $this->getConnectionData()->getPassword(),
        ];

        $dbConfig = [
            'connection' => [
                'default' => $connectionData,
                'indexer' => $connectionData,
            ],
        ];

        if ($this->stageConfig->get(DeployInterface::VAR_MYSQL_USE_SLAVE_CONNECTION)
            && $this->isDbConfigurationCompatibleWithSlaveConnection()
        ) {
            $slaveConfiguration = $this->slaveConfig->get();

            if (!empty($slaveConfiguration)) {
                $dbConfig['slave_connection']['default'] = $slaveConfiguration;
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
     * @return boolean
     */
    public function isDbConfigurationCompatibleWithSlaveConnection(): bool
    {
        $envDbConfig = $this->stageConfig->get(DeployInterface::VAR_DATABASE_CONFIGURATION);

        if ((isset($envDbConfig['connection']['default']['host'])
                && $envDbConfig['connection']['default']['host'] !== $this->getConnectionData()->getHost())
            || (isset($envDbConfig['connection']['default']['dbname'])
                && $envDbConfig['connection']['default']['dbname'] !== $this->getConnectionData()->getDbName())
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

    /**
     * Returns connection data from relationship array
     *
     * @return ConnectionInterface
     */
    private function getConnectionData(): ConnectionInterface
    {
        if (!$this->connectionData instanceof ConnectionInterface) {
            $this->connectionData = $this->connectionFactory->create(RelationshipConnectionFactory::CONNECTION_MAIN);
        }

        return $this->connectionData;
    }
}
