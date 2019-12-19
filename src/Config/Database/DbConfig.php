<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Config\Database;

use Magento\MagentoCloud\Config\ConfigMerger;
use Magento\MagentoCloud\Config\Stage\DeployInterface;
use Magento\MagentoCloud\DB\Data\ConnectionInterface;
use Magento\MagentoCloud\DB\Data\RelationshipConnectionFactory;

/**
 * Returns merged database configuration from the .magento.env.yaml file
 * and environment variable MAGENTO_CLOUD_RELATIONSHIPS.
 * Database configuration includes all all types of connections: main connection, slave connection
 * and include split connections if they exist. Exception: custom split connections from .magento.env.yaml are ignored.
 */
class DbConfig implements ConfigInterface
{
    /**
     * Keys for the description of the database configuration
     */
    const KEY_DB = 'db';
    const KEY_CONNECTION = 'connection';
    const KEY_SLAVE_CONNECTION = 'slave_connection';
    const KEY_HOST = 'host';
    const KEY_USERNAME = 'username';
    const KEY_DBNAME = 'dbname';
    const KEY_PASSWORD = 'password';
    const KEY_MODEL = 'model';
    const KEY_ENGINE = 'engine';
    const KEY_INIT_STATEMENTS = 'initStatements';
    const KEY_ACTIVE = 'active';

    /**
     * Names of connections
     */
    const CONNECTION_DEFAULT = 'default';
    const CONNECTION_INDEXER = 'indexer';
    const CONNECTION_CHECKOUT = 'checkout';
    const CONNECTION_SALE = 'sale';

    /**
     * Types of connections
     */
    const CONNECTION_TYPES = [self::KEY_CONNECTION, self::KEY_SLAVE_CONNECTION];

    /**
     * Main connections
     */
    const MAIN_CONNECTIONS = [self::CONNECTION_DEFAULT, self::CONNECTION_INDEXER];

    /**
     * Split connections
     */
    const SPLIT_CONNECTIONS = [self::CONNECTION_CHECKOUT, self::CONNECTION_SALE];

    /**
     * Main connection map with data from environment relationship connections
     */
    const MAIN_CONNECTION_MAP = [
        self::CONNECTION_DEFAULT => RelationshipConnectionFactory::CONNECTION_MAIN,
        self::CONNECTION_INDEXER => RelationshipConnectionFactory::CONNECTION_MAIN,
        self::CONNECTION_CHECKOUT => RelationshipConnectionFactory::CONNECTION_QUOTE_MAIN,
        self::CONNECTION_SALE => RelationshipConnectionFactory::CONNECTION_SALES_MAIN,
    ];

    /**
     * Slave connection map
     */
    const SLAVE_CONNECTION_MAP = [
        self::CONNECTION_DEFAULT => RelationshipConnectionFactory::CONNECTION_SLAVE,
        self::CONNECTION_CHECKOUT => RelationshipConnectionFactory::CONNECTION_QUOTE_SLAVE,
        self::CONNECTION_SALE => RelationshipConnectionFactory::CONNECTION_SALES_SLAVE,
    ];

    /**
     * Final configuration for deploy phase
     *
     * @var DeployInterface
     */
    private $stageConfig;

    /**
     * Class for configuration merging
     *
     * @var ConfigMerger
     */
    private $configMerger;

    /**
     * Connection data from relationship array
     *
     * @var array
     */
    private $envConnectionData;

    /**
     * Factory for creation database configurations
     *
     * @var RelationshipConnectionFactory
     */
    private $connectionDataFactory;

    /**
     * Final database configuration after merging
     *
     * @var array
     */
    private $dbConfig;

    /**
     * @param DeployInterface $stageConfig
     * @param ConfigMerger $configMerger
     * @param RelationshipConnectionFactory $connectionDataFactory
     */
    public function __construct(
        DeployInterface $stageConfig,
        ConfigMerger $configMerger,
        RelationshipConnectionFactory $connectionDataFactory
    ) {
        $this->stageConfig = $stageConfig;
        $this->configMerger = $configMerger;
        $this->connectionDataFactory = $connectionDataFactory;
    }

    /**
     * Returns database configurations as merge result of customer configurations from .magento.env.yaml
     * and connection data from cloud environment
     *
     * @return array
     */
    public function get(): array
    {
        if (null !== $this->dbConfig) {
            return $this->dbConfig;
        }

        $customConfig = $this->getCustomDbConfig();

        if (!$this->configMerger->isEmpty($customConfig) && !$this->configMerger->isMergeRequired($customConfig)) {
            return $this->configMerger->clear($customConfig);
        }

        return $this->dbConfig = $this->configMerger->merge($this->createDbConfig($customConfig), $customConfig);
    }

    /**
     *  Creates Database configuration
     *
     * @param array $customDbConfig
     * @return array
     */
    private function createDbConfig(array $customDbConfig): array
    {
        $config = [];
        foreach (self::MAIN_CONNECTION_MAP as $connection => $service) {
            $envDBConfig = $this->getEnvConnectionData($service);
            if (empty($envDBConfig->getHost())) {
                continue;
            }
            $config[self::KEY_CONNECTION][$connection] = $this->convertToMageDbConfig(
                $envDBConfig,
                !in_array($connection, self::MAIN_CONNECTIONS)
            );
            if (!isset(self::SLAVE_CONNECTION_MAP[$connection])) {
                continue;
            }
            $slaveConnectionData = $this->getEnvConnectionData(self::SLAVE_CONNECTION_MAP[$connection]);
            if (empty($slaveConnectionData->getHost())
                || !$this->isDbConfigCompatibleWithSlaveConnection($customDbConfig, $connection, $envDBConfig)) {
                continue;
            }
            $config[self::KEY_SLAVE_CONNECTION][$connection] = $this->convertToMageDbConfig($slaveConnectionData, true);
        }
        return $config;
    }

    /**
     * Returns a custom database configuration from the variable DATABASE_CONFIGURATION from .magento.env.yaml
     *
     * @return array
     */
    private function getCustomDbConfig(): array
    {
        $envConfig = (array)$this->stageConfig->get(DeployInterface::VAR_DATABASE_CONFIGURATION);

        /**
         * Ece-tools do not support custom configuration of a split database.
         */
        foreach (self::SPLIT_CONNECTIONS as $connection) {
            foreach (self::CONNECTION_TYPES as $connectionType) {
                if (isset($envConfig[$connectionType][$connection])) {
                    unset($envConfig[$connectionType][$connection]);
                }
            }
        }
        return $envConfig;
    }

    /**
     * Checks that database configuration was changed in DATABASE_CONFIGURATION variable
     * in not compatible way with slave_connection.
     *
     * Returns true if $envDbConfig contains host or dbname for default connection
     * that doesn't match connection from relationships,
     * otherwise return false.
     *
     * @param string $connectionName
     * @param ConnectionInterface $connectionData
     * @param array $envDbConfig database configuration from DATABASE_CONFIGURATION of .magento.env.yaml
     * @return boolean
     */
    public function isDbConfigCompatibleWithSlaveConnection(
        array $envDbConfig,
        string $connectionName,
        ConnectionInterface $connectionData
    ): bool {
        if ((isset($envDbConfig[self::KEY_CONNECTION][$connectionName]['host'])
                && $envDbConfig[self::KEY_CONNECTION][$connectionName]['host'] !== $connectionData->getHost())
            || (isset($envDbConfig[self::KEY_CONNECTION][$connectionName]['dbname'])
                && $envDbConfig[self::KEY_CONNECTION][$connectionName]['dbname'] !== $connectionData->getDbName())
        ) {
            return false;
        }

        return true;
    }

    /**
     * Returns connection data from relationship array
     *
     * @param string $service
     * @return ConnectionInterface
     */
    private function getEnvConnectionData(string $service): ConnectionInterface
    {
        if (!isset($this->envConnectionData[$service])
            || !($this->envConnectionData[$service] instanceof ConnectionInterface)) {
            $this->envConnectionData[$service] = $this->connectionDataFactory->create($service);
        }

        return $this->envConnectionData[$service];
    }

    /**
     * Convert DB configuration to format which is used in Magento configuration file
     *
     * @param ConnectionInterface $connectionData
     * @param bool $additionalParams
     * @return array
     */
    private function convertToMageDbConfig(ConnectionInterface $connectionData, bool $additionalParams = false): array
    {
        $host = $connectionData->getHost();

        if (!$host) {
            return [];
        }

        $port = $connectionData->getPort();

        $config = [
            self::KEY_HOST => empty($port) || $port == '3306' ? $host : $host . ':' . $port,
            self::KEY_USERNAME => $connectionData->getUser(),
            self::KEY_DBNAME => $connectionData->getDbName(),
            self::KEY_PASSWORD => $connectionData->getPassword(),
        ];

        if ($additionalParams) {
            $config[self::KEY_MODEL] = 'mysql4';
            $config[self::KEY_ENGINE] = 'innodb';
            $config[self::KEY_INIT_STATEMENTS] = 'SET NAMES utf8;';
            $config[self::KEY_ACTIVE] = '1';
        }
        return $config;
    }
}
