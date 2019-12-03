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
class MergedConfig implements ConfigInterface
{
    const RESOURCE = 'resource';

    /**
     * Names fo resources
     */
    const RESOURCE_CHECKOUT = 'checkout';
    const RESOURCE_SALES = 'sales';
    const RESOURCE_DEFAULT_SETUP = 'default_setup';

    const DB = 'db';
    const CONNECTION = 'connection';

    /**
     * Names of connections
     */
    const SLAVE_CONNECTION = 'slave_connection';
    const CONNECTION_DEFAULT = 'default';
    const CONNECTION_INDEXER = 'indexer';
    const CONNECTION_CHECKOUT = 'checkout';
    const CONNECTION_SALES = 'sales';

    /**
     * Types of connections
     */
    const CONNECTION_TYPES = [self::CONNECTION, self::SLAVE_CONNECTION];

    /**
     * Split connections
     */
    const SPLIT_CONNECTIONS = [self::CONNECTION_CHECKOUT, self::CONNECTION_SALES];

    /**
     * Connections map
     */
    const CONNECTION_MAP = [
        self::CONNECTION => [
            self::CONNECTION_DEFAULT => RelationshipConnectionFactory::CONNECTION_MAIN,
            self::CONNECTION_INDEXER => RelationshipConnectionFactory::CONNECTION_MAIN,
            self::CONNECTION_CHECKOUT => RelationshipConnectionFactory::CONNECTION_QUOTE_MAIN,
            self::CONNECTION_SALES => RelationshipConnectionFactory::CONNECTION_SALES_MAIN,
        ],
        self::SLAVE_CONNECTION => [
            self::CONNECTION_DEFAULT => RelationshipConnectionFactory::CONNECTION_SLAVE,
            self::CONNECTION_CHECKOUT => RelationshipConnectionFactory::CONNECTION_QUOTE_SLAVE,
            self::CONNECTION_SALES => RelationshipConnectionFactory::CONNECTION_SALES_SLAVE,
        ]
    ];

    /**
     * Resources map
     */
    const RESOURCE_MAP = [
        self::CONNECTION_DEFAULT => self::RESOURCE_DEFAULT_SETUP,
        self::CONNECTION_CHECKOUT => self::RESOURCE_CHECKOUT,
        self::CONNECTION_SALES => self::RESOURCE_SALES,
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
     * Returns database and resource configurations
     *
     * Returns
     * ```
     * [
     *     'db' => [...]       // Database configuration
     *     'resource' => [...] // Resource configuration
     * ]
     *
     * ```
     *
     * @return array
     */
    public function get(): array
    {
        if ($this->mergedConfig !== null) {
            return $this->mergedConfig;
        }

        $connections = $this->getConnections();
        return $this->mergedConfig = [
            self::DB => $connections,
            self::RESOURCE => $this->getResources(array_keys($connections[self::CONNECTION])),
        ];
    }


    private function getConnections(): array
    {
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
        foreach (self::CONNECTION_MAP[self::CONNECTION] as $connectionName => $serviceKey) {
            $connectionData = $this->getConnectionData($serviceKey);
            if (empty($connectionData->getHost())) {
                continue;
            }
            $config[self::CONNECTION][$connectionName] = $this->getConnectionConfig($connectionData);
            if (!$useSlave && !isset(self::CONNECTION_MAP[self::SLAVE_CONNECTION][$connectionName])) {
                continue;
            }
            $connectionData = $this->getConnectionData(self::CONNECTION_MAP[self::SLAVE_CONNECTION][$connectionName]);
            if (empty($connectionData->getHost())
                || !$this->isDbConfigCompatibleWithSlaveConnection($connectionName)) {
                continue;
            }
            $config[self::SLAVE_CONNECTION][$connectionName] = $this->getConnectionConfig($connectionData, true);
        }

        return $this->configMerger->merge($config, $envConfig);
    }

    /**
     * Returns resource configuration
     * @param array $connections
     * @return array
     */
    private function getResources(array $connections): array
    {
        $envConfig = $this->stageConfig->get(DeployInterface::VAR_RESOURCE_CONFIGURATION);

        foreach (self::RESOURCE_MAP as $connectionName => $resourceName) {
            if (in_array($connectionName, self::SPLIT_CONNECTIONS)
                && isset($envConfig[$resourceName])) {
                unset($envConfig[$resourceName]);
            }
        }

        if (!$this->configMerger->isEmpty($envConfig) && !$this->configMerger->isMergeRequired($envConfig)) {
            return $this->configMerger->clear($envConfig);
        }

        $config = [];

        foreach ($connections as $connectionName) {
            if (isset(self::RESOURCE_MAP[$connectionName])) {
                $config[self::RESOURCE_MAP[$connectionName]][self::CONNECTION] = $connectionName;
            }
        }

        return $this->configMerger->merge($config, $envConfig);
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
        $config = $this->stageConfig->get(DeployInterface::VAR_DATABASE_CONFIGURATION);
        $connectionData = $this->getConnectionData(self::CONNECTION_MAP[self::CONNECTION][$connectionName]);
        if ((isset($config[self::CONNECTION][$connectionName]['host'])
                && $config[self::CONNECTION][$connectionName]['host'] !== $connectionData->getHost())
            || (isset($config[self::CONNECTION][$connectionName]['dbname'])
                && $config[self::CONNECTION][$connectionName]['dbname'] !== $connectionData->getDbName())
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
    public function getConnectionConfig(ConnectionInterface $connectionData, $isSlave = false): array
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
