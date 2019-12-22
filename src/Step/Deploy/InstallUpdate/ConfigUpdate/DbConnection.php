<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Step\Deploy\InstallUpdate\ConfigUpdate;

use Magento\MagentoCloud\Config\ConfigMerger;
use Magento\MagentoCloud\Config\Database\DbConfig;
use Magento\MagentoCloud\Config\Database\ResourceConfig;
use Magento\MagentoCloud\Config\Magento\Env\ReaderInterface as ConfigReader;
use Magento\MagentoCloud\Config\Magento\Env\WriterInterface as ConfigWriter;
use Magento\MagentoCloud\Config\Stage\DeployInterface;
use Magento\MagentoCloud\DB\Data\RelationshipConnectionFactory;
use Magento\MagentoCloud\Filesystem\Flag\Manager as FlagManager;
use Magento\MagentoCloud\Step\Deploy\SplitDbConnection;
use Magento\MagentoCloud\Step\StepInterface;
use Psr\Log\LoggerInterface;

/**
 * Updates DB connection configuration.
 */
class DbConnection implements StepInterface
{
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
     * @var DbConfig
     */
    private $dbConfig;

    /**
     * @var ResourceConfig
     */
    private $resourceConfig;

    /**
     * @var DeployInterface
     */
    private $stageConfig;

    /**
     * @var ConfigMerger
     */
    private $configMerger;

    /**
     * @var RelationshipConnectionFactory
     */
    private $connectionDataFactory;

    /**
     * @var FlagManager
     */
    private $flagManager;

    /**
     * @param DeployInterface $stageConfig
     * @param DbConfig $dbConfig
     * @param ResourceConfig $resourceConfig
     * @param ConfigWriter $configWriter
     * @param ConfigReader $configReader
     * @param ConfigMerger $configMerger
     * @param RelationshipConnectionFactory $connectionDataFactory
     * @param LoggerInterface $logger
     * @param FlagManager $flagManager
     */
    public function __construct(
        DeployInterface $stageConfig,
        DbConfig $dbConfig,
        ResourceConfig $resourceConfig,
        ConfigWriter $configWriter,
        ConfigReader $configReader,
        ConfigMerger $configMerger,
        RelationshipConnectionFactory $connectionDataFactory,
        LoggerInterface $logger,
        FlagManager $flagManager
    ) {
        $this->stageConfig = $stageConfig;
        $this->dbConfig = $dbConfig;
        $this->resourceConfig = $resourceConfig;
        $this->configWriter = $configWriter;
        $this->configReader = $configReader;
        $this->configMerger = $configMerger;
        $this->logger = $logger;
        $this->connectionDataFactory = $connectionDataFactory;
        $this->flagManager = $flagManager;
    }

    /**
     * @inheritdoc
     */
    public function execute()
    {
        $this->logger->info('Updating env.php DB connection configuration.');

        $dbConfig = $this->dbConfig->get();

        if (!isset($dbConfig[DbConfig::KEY_CONNECTION])) {
            /**
             * Is calling only in case when database relationship configuration doesn't exist and
             * database is not configured through .magento.env.yaml or env variable.
             * It's workaround for scenarios when magento was installed by raw setup:install command
             * not by deploy scripts.
             */
            $this->logger->notice(
                'Database relationship configuration doesn\'t exist'
                . ' and database is not configured through .magento.env.yaml or env variable.'
                . ' Will be applied the previous database configuration.'
            );
            return;
        }

        $mageConfig = $this->configReader->read();
        $mageSplitConnectionsConfig = array_intersect_key(
            $mageConfig[DbConfig::KEY_DB][DbConfig::KEY_CONNECTION],
            array_flip(DbConfig::SPLIT_CONNECTIONS)
        );

        $customSplitConnections = $this->getDifferentConnections(
            $mageSplitConnectionsConfig,
            $dbConfig[DbConfig::KEY_CONNECTION]
        );
        $missedTypes = $this->getMissedSplitTypesFromVarSplitDB(array_keys($mageSplitConnectionsConfig));

        if (!empty($customSplitConnections)) {
            $this->logger->warning(sprintf(
                'For split databases used custom connections: %s',
                implode(', ', $customSplitConnections)
            ));
            $this->flagManager->set(FlagManager::FLAG_IGNORE_SPLIT_DB);
            return;
        }
        if (!empty($missedTypes)) {
            $this->logger->warning(sprintf(
                'Db %s was split before, but SPLIT_DB does not have this info',
                implode(', ', $missedTypes)
            ));
            return;
        }

        $this->updateMageConfig($mageConfig, $dbConfig);
    }

    /**
     * Returns connections whose hosts are different
     *
     * @param array $connectionsConfig1
     * @param array $connectionsConfig2
     * @return array
     */
    private function getDifferentConnections(array $connectionsConfig1, array $connectionsConfig2): array
    {
        $differentConnections = [];
        foreach ($connectionsConfig1 as $connectionName => $connectionData) {
            if (isset($connectionsConfig2[$connectionName])
                && !$this->isSameConnection($connectionsConfig2[$connectionName], $connectionData)) {
                $differentConnections[] = $connectionName;
            }
        }
        return $differentConnections;
    }

    /**
     * Checks connection parameters for equality
     *
     * @param array $connection1
     * @param array $connection2
     * @return bool
     */
    private function isSameConnection(array $connection1, array $connection2): bool
    {
        return $connection1[DbConfig::KEY_HOST] === $connection2[DbConfig::KEY_HOST];
    }

    /**
     * Returns missed split types from VAR SPLIT_DB variable form .magento.env.yaml
     * @param array $connections
     * @return array
     */
    private function getMissedSplitTypesFromVarSplitDB(array $connections)
    {
        $varSplitDb = $this->stageConfig->get(DeployInterface::VAR_SPLIT_DB);
        $types = [];
        foreach ($connections as $connectionName) {
            $type = SplitDbConnection::SPLIT_CONNECTION_MAP[$connectionName];
            if (!in_array($type, $varSplitDb)) {
                $types[] = $type;
            }
        }
        return $types;
    }

    /**
     * Updates app/etc/env.php configuration
     * @param array $mageConfig
     * @param array $dbConfig
     * @throws \Magento\MagentoCloud\Filesystem\FileSystemException
     */
    private function updateMageConfig(array $mageConfig, array $dbConfig)
    {
        $isCustomDefaultConnection = !$this->isSameConnection(
            $dbConfig[DbConfig::KEY_CONNECTION][DbConfig::CONNECTION_DEFAULT],
            $mageConfig[DbConfig::KEY_DB][DbConfig::KEY_CONNECTION][DbConfig::CONNECTION_DEFAULT]
        );

        if (!empty($mageSplitConnectionsConfig) && $isCustomDefaultConnection) {
            $this->logger->notice(
                'Database was already split but deploy was configured with new connection.'
                . ' The previous connection data will be ignored.'
            );
        }

        $useSlave = $this->stageConfig->get(DeployInterface::VAR_MYSQL_USE_SLAVE_CONNECTION);
        $slaveIsAvailable = isset($dbConfig[DbConfig::KEY_SLAVE_CONNECTION]);

        if (empty($mageSplitConnectionsConfig)
            || (!empty($mageSplitConnectionsConfig) && $isCustomDefaultConnection)) {
            $mageConfig[DbConfig::KEY_DB] = $this->getMainDbConfig($dbConfig, $useSlave && $slaveIsAvailable);
            $mageConfig[ResourceConfig::KEY_RESOURCE] = $this->getMainResourceConfig();
        } elseif ($useSlave && $slaveIsAvailable) {
            $slaveConnectionsConfig = $this->getMainConnections($dbConfig[DbConfig::KEY_SLAVE_CONNECTION]);
            $mageConfig[DbConfig::KEY_DB][DbConfig::KEY_SLAVE_CONNECTION] = $slaveConnectionsConfig;
        } else {
            unset($mageConfig[DbConfig::KEY_DB][DbConfig::KEY_SLAVE_CONNECTION]);
        }

        $this->addLoggingAboutSlaveConnection($mageConfig[DbConfig::KEY_DB], $useSlave);

        $this->configWriter->create($mageConfig);
    }

    /**
     * Returns database configuration with default and slave connections
     *
     * @param array $dbConfig
     * @param bool $withSlave
     * @return array
     */
    private function getMainDbConfig(array $dbConfig, bool $withSlave): array
    {
        $dbConfig[DbConfig::KEY_CONNECTION] = $this->getMainConnections($dbConfig[DbConfig::KEY_CONNECTION]);

        if ($withSlave) {
            $slaveConnections = $dbConfig[DbConfig::KEY_SLAVE_CONNECTION];
            $dbConfig[DbConfig::KEY_SLAVE_CONNECTION] = $this->getMainConnections($slaveConnections);
        }

        return $dbConfig;
    }

    /**
     * Returns main resource configuration
     *
     * @return array
     */
    private function getMainResourceConfig(): array
    {
        return array_intersect_key(
            $this->resourceConfig->get(),
            array_flip([ResourceConfig::RESOURCE_DEFAULT_SETUP])
        );
    }

    /**
     * Returns main connection configurations
     *
     * @param array $connections
     * @return array
     */
    private function getMainConnections(array $connections): array
    {
        return array_intersect_key(
            $connections,
            array_flip(DbConfig::MAIN_CONNECTIONS)
        );
    }

    /**
     * Adds logging about slave connection.
     *
     * @param array $dbConfig
     * @param bool $isUseSlave
     */
    private function addLoggingAboutSlaveConnection(array $dbConfig, bool $isUseSlave)
    {
        $customDbConfig = $this->stageConfig->get(DeployInterface::VAR_DATABASE_CONFIGURATION);
        $isMergeRequired = !$this->configMerger->isEmpty($customDbConfig)
            && !$this->configMerger->isMergeRequired($customDbConfig);
        foreach (array_keys($dbConfig[DbConfig::KEY_CONNECTION]) as $connectionName) {
            $connectionData = $this->connectionDataFactory->create(DbConfig::MAIN_CONNECTION_MAP[$connectionName]);
            if (!$connectionData->getHost() || !$isUseSlave || $isMergeRequired) {
                continue;
            }
            if (!$this->dbConfig->isDbConfigCompatibleWithSlaveConnection(
                $customDbConfig,
                $connectionName,
                $connectionData
            )) {
                $this->logger->warning(sprintf(
                    'You have changed db configuration that not compatible with %s slave connection.',
                    $connectionName
                ));
            } elseif (!empty($dbConfig[DbConfig::KEY_SLAVE_CONNECTION][$connectionName])) {
                $this->logger->info(sprintf('Set DB slave connection for %s connection.', $connectionName));
            } else {
                $this->logger->notice(sprintf(
                    'Enabling of the variable MYSQL_USE_SLAVE_CONNECTION had no effect for %s connection, ' .
                    'because %s slave connection is not configured on your environment.',
                    $connectionName,
                    $connectionName
                ));
            }
        }
    }
}
