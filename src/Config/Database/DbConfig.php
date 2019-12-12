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
 * Returns merged final database configuration.
 */
class DbConfig implements ConfigInterface
{
    const KEY_DB = 'db';
    const KEY_CONNECTION = 'connection';
    const KEY_SLAVE_CONNECTION = 'slave_connection';

    /**
     * Names of connections
     */
    const CONNECTION_DEFAULT = 'default';
    const CONNECTION_INDEXER = 'indexer';
    const CONNECTION_CHECKOUT = 'checkout';
    const CONNECTION_SALE = 'sale';

    /**
     * Connection list
     */
    const CONNECTIONS = [
        self::CONNECTION_DEFAULT,
        self::CONNECTION_INDEXER,
        self::CONNECTION_CHECKOUT,
        self::CONNECTION_SALE,
    ];

    /**
     * Types of connections
     */
    const CONNECTION_TYPES = [self::KEY_CONNECTION, self::KEY_SLAVE_CONNECTION];

    /**
     * Default connections
     */
    const MAIN_CONNECTIONS = [self::CONNECTION_DEFAULT, self::CONNECTION_INDEXER];

    /**
     * Split connections
     */
    const SPLIT_CONNECTIONS = [self::CONNECTION_CHECKOUT, self::CONNECTION_SALE];

    /**
     * Connections map
     */
    const CONNECTION_MAP = [
        self::KEY_CONNECTION => [
            self::CONNECTION_DEFAULT => RelationshipConnectionFactory::CONNECTION_MAIN,
            self::CONNECTION_INDEXER => RelationshipConnectionFactory::CONNECTION_MAIN,
            self::CONNECTION_CHECKOUT => RelationshipConnectionFactory::CONNECTION_QUOTE_MAIN,
            self::CONNECTION_SALE => RelationshipConnectionFactory::CONNECTION_SALES_MAIN,
        ],
        self::KEY_SLAVE_CONNECTION => [
            self::CONNECTION_DEFAULT => RelationshipConnectionFactory::CONNECTION_SLAVE,
            self::CONNECTION_CHECKOUT => RelationshipConnectionFactory::CONNECTION_QUOTE_SLAVE,
            self::CONNECTION_SALE => RelationshipConnectionFactory::CONNECTION_SALES_SLAVE,
        ]
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
    private $connectionData;

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
    private $mergedConfig;

    /**
     * @param ConfigMerger $configMerger
     * @param DeployInterface $stageConfig
     * @param RelationshipConnectionFactory $connectionDataFactory
     */
    public function __construct(
        ConfigMerger $configMerger,
        DeployInterface $stageConfig,
        RelationshipConnectionFactory $connectionDataFactory
    )
    {
        $this->connectionDataFactory = $connectionDataFactory;
        $this->stageConfig = $stageConfig;
        $this->configMerger = $configMerger;
    }

    /**
     * Returns database configurations
     *
     * @return array
     */
    public function get(): array
    {
        if (null !== $this->mergedConfig) {
            return $this->mergedConfig;
        }

        $envConfig = $this->stageConfig->get(DeployInterface::VAR_DATABASE_CONFIGURATION);

        foreach (self::SPLIT_CONNECTIONS as $connectionName) {
            foreach (self::CONNECTION_TYPES as $connectionType) {
                if (isset($envConfig[$connectionType][$connectionName])) {
                    unset($envConfig[$connectionType][$connectionName]);
                }
            }
        }

        if (!$this->configMerger->isEmpty($envConfig) && !$this->configMerger->isMergeRequired($envConfig)) {
            return $this->configMerger->clear($envConfig);
        }

        $useSlave = $this->stageConfig->get(DeployInterface::VAR_MYSQL_USE_SLAVE_CONNECTION);

        $config = [];
        foreach (self::CONNECTION_MAP[self::KEY_CONNECTION] as $connectionName => $serviceKey) {
            $connectionData = $this->getConnectionData($serviceKey);
            if (empty($connectionData->getHost())) {
                continue;
            }
            $config[self::KEY_CONNECTION][$connectionName] = $this->getConnectionConfig($connectionData);
            if (!$useSlave || !isset(self::CONNECTION_MAP[self::KEY_SLAVE_CONNECTION][$connectionName])) {
                continue;
            }
            $connectionData = $this->getConnectionData(self::CONNECTION_MAP[self::KEY_SLAVE_CONNECTION][$connectionName]);
            if (empty($connectionData->getHost())
                || !$this->isDbConfigCompatibleWithSlaveConnection($connectionName)) {
                continue;
            }
            $config[self::KEY_SLAVE_CONNECTION][$connectionName] = $this->getConnectionConfig($connectionData, true);
        }

        return $this->mergedConfig = $this->configMerger->merge($config, $envConfig);
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
     * @return boolean
     */
    public function isDbConfigCompatibleWithSlaveConnection(string $connectionName): bool
    {
        $envDbConfig = $this->stageConfig->get(DeployInterface::VAR_DATABASE_CONFIGURATION);
        $connectionData = $this->getConnectionData(self::CONNECTION_MAP[self::KEY_CONNECTION][$connectionName]);
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
     * @param string $serviceKey
     * @return ConnectionInterface
     */
    private function getConnectionData(string $serviceKey): ConnectionInterface
    {
        if (!isset($this->connectionData[$serviceKey]) || !($this->connectionData[$serviceKey] instanceof ConnectionInterface)) {
            $this->connectionData[$serviceKey] = $this->connectionDataFactory->create($serviceKey);
        }

        return $this->connectionData[$serviceKey];
    }

    /**
     * Returns configuration for connection
     *
     * @param ConnectionInterface $connectionData
     * @param bool $isSlave
     * @return array
     */
    private function getConnectionConfig(ConnectionInterface $connectionData, $isSlave = false): array
    {
        $host = $connectionData->getHost();

        if (!$host) {
            return [];
        }

        $port = $connectionData->getPort();

        $config = [
            'host' => empty($port) || $port == '3306' ? $host : $host . ':' . $port,
            'username' => $connectionData->getUser(),
            'dbname' => $connectionData->getDbName(),
            'password' => $connectionData->getPassword(),
        ];
        if ($isSlave) {
            $config['model'] = 'mysql4';
            $config['engine'] = 'innodb';
            $config['initStatements'] = 'SET NAMES utf8;';
            $config['active'] = '1';
        }
        return $config;
    }
}
