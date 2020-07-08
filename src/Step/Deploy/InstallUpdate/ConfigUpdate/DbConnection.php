<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Step\Deploy\InstallUpdate\ConfigUpdate;

use Magento\MagentoCloud\App\Error;
use Magento\MagentoCloud\App\GenericException;
use Magento\MagentoCloud\Config\ConfigException;
use Magento\MagentoCloud\Config\ConfigMerger;
use Magento\MagentoCloud\Config\Database\DbConfig;
use Magento\MagentoCloud\Config\Database\ResourceConfig;
use Magento\MagentoCloud\Config\Magento\Env\ReaderInterface as ConfigReader;
use Magento\MagentoCloud\Config\Magento\Env\WriterInterface as ConfigWriter;
use Magento\MagentoCloud\Config\Stage\DeployInterface;
use Magento\MagentoCloud\DB\Data\RelationshipConnectionFactory;
use Magento\MagentoCloud\Filesystem\FileSystemException;
use Magento\MagentoCloud\Filesystem\Flag\ConfigurationMismatchException;
use Magento\MagentoCloud\Filesystem\Flag\Manager as FlagManager;
use Magento\MagentoCloud\Step\StepException;
use Magento\MagentoCloud\Step\StepInterface;
use Psr\Log\LoggerInterface;

/**
 * Updates DB connection configuration.
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
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
    private $connectionFactory;

    /**
     * @var FlagManager
     */
    private $flagManager;

    /**
     * Cached configuration from app/etc/env.php
     *
     * @var array
     */
    private $mageConfigData;

    /**
     * Cached database configuration from environment
     *
     * @var array
     */
    private $dbConfigData;

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
        $this->connectionFactory = $connectionDataFactory;
        $this->flagManager = $flagManager;
    }

    /**
     * Sets the database configuration to the configuration file app/etc/env.php
     * In the case when the database was split with the user configuration then sets the flag '.ignore_split_db'
     * If the flag '.ignore_split_db' exists, the split process will be ignored
     *
     * @throws FileSystemException
     * @throws ConfigException
     * @throws ConfigurationMismatchException
     */
    public function execute()
    {
        try {
            $dbConfig = $this->getDbConfigData();

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

            $this->logger->info('Updating env.php DB connection configuration.');

            $mageSplitConnectionsConfig = $this->getMageSplitConnectionsConfig();

            $useSlave = $this->stageConfig->get(DeployInterface::VAR_MYSQL_USE_SLAVE_CONNECTION);

            if (empty($mageSplitConnectionsConfig)) {
                $this->setOnlyMainConnectionsConfig($useSlave);
                return;
            }

            $customSplitConnections = $this->getDifferentConnections(
                $mageSplitConnectionsConfig,
                $dbConfig[DbConfig::KEY_CONNECTION]
            );

            if (!empty($customSplitConnections)) {
                $this->logger->warning(
                    sprintf('For split databases used custom connections: %s', implode(', ', $customSplitConnections)),
                    ['errorCode' => Error::WARN_SPLIT_DB_CUSTOM_CONNECTION_USED]
                );
                $this->flagManager->set(FlagManager::FLAG_IGNORE_SPLIT_DB);
                return;
            }

            $this->updateConnectionsConfig($mageSplitConnectionsConfig, $useSlave);
        } catch (GenericException $e) {
            throw new StepException($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * Establishes basic connection configurations of app/etc/env.php
     *
     * @param bool $useSlave
     * @throws FileSystemException
     * @throws ConfigException
     */
    public function setOnlyMainConnectionsConfig(bool $useSlave)
    {
        $mageConfig = $this->getMageConfigData();
        $mageConfig[DbConfig::KEY_DB] = $this->getMainDbConfig($useSlave);
        $mageConfig[ResourceConfig::KEY_RESOURCE] = $this->getMainResourceConfig();
        if ($useSlave) {
            $this->addLoggingAboutSlaveConnection($mageConfig[DbConfig::KEY_DB]);
        }
        $this->configWriter->create($mageConfig);
    }

    /**
     * Updates db configurations
     *
     * @param array $mageSplitConnectionsConfig
     * @param bool $useSlave
     * @throws FileSystemException
     * @throws ConfigException
     */
    private function updateConnectionsConfig(array $mageSplitConnectionsConfig, bool $useSlave)
    {
        $dbConfig = $this->getDbConfigData();
        foreach (DbConfig::SPLIT_CONNECTIONS as $splitConnection) {
            if (isset($mageSplitConnectionsConfig[$splitConnection])) {
                $dbConfig[DbConfig::KEY_CONNECTION][$splitConnection] = $mageSplitConnectionsConfig[$splitConnection];
            } elseif (isset($dbConfig[DbConfig::KEY_CONNECTION][$splitConnection])) {
                unset($dbConfig[DbConfig::KEY_CONNECTION][$splitConnection]);
            }
        }

        if ($useSlave && $this->slaveIsAvailable()) {
            $slaveConnectionsConfig = $this->getMainConnections($dbConfig[DbConfig::KEY_SLAVE_CONNECTION]);
            $dbConfig[DbConfig::KEY_SLAVE_CONNECTION] = $slaveConnectionsConfig;
            $this->addLoggingAboutSlaveConnection($dbConfig);
        } else {
            unset($dbConfig[DbConfig::KEY_SLAVE_CONNECTION]);
        }

        $mageConfig = $this->getMageConfigData();
        $mageConfig[DbConfig::KEY_DB] = $dbConfig;
        $this->configWriter->create($mageConfig);
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
     * Returns database configuration with default and slave connections
     *
     * @param bool $withSlave
     * @return array
     * @throws ConfigException
     */
    private function getMainDbConfig(bool $withSlave): array
    {
        $dbConfig = $this->getDbConfigData();
        $dbConfig[DbConfig::KEY_CONNECTION] = $this->getMainConnections($dbConfig[DbConfig::KEY_CONNECTION]);

        if ($withSlave && $this->slaveIsAvailable()) {
            $slaveConnections = $dbConfig[DbConfig::KEY_SLAVE_CONNECTION];
            $dbConfig[DbConfig::KEY_SLAVE_CONNECTION] = $this->getMainConnections($slaveConnections);
        } else {
            unset($dbConfig[DbConfig::KEY_SLAVE_CONNECTION]);
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
        return array_intersect_key($this->resourceConfig->get(), array_flip([ResourceConfig::RESOURCE_DEFAULT_SETUP]));
    }

    /**
     * Returns main connection configurations
     *
     * @param array $connections
     * @return array
     */
    private function getMainConnections(array $connections): array
    {
        return array_intersect_key($connections, array_flip(DbConfig::MAIN_CONNECTIONS));
    }

    /**
     * Adds logging about slave connection.
     *
     * @param array $dbConfig
     * @throws ConfigException
     */
    private function addLoggingAboutSlaveConnection(array $dbConfig)
    {
        $connectionData = $this->connectionFactory->create(DbConfig::MAIN_CONNECTION_MAP[DbConfig::CONNECTION_DEFAULT]);
        $customDbConfig = $this->stageConfig->get(DeployInterface::VAR_DATABASE_CONFIGURATION);
        if (!$connectionData->getHost()
            || (!$this->configMerger->isEmpty($customDbConfig)
                && !$this->configMerger->isMergeRequired($customDbConfig))
        ) {
            return;
        } elseif (!$this->dbConfig->isCustomConnectionCompatibleForSlave(
            $customDbConfig,
            DbConfig::CONNECTION_DEFAULT,
            $connectionData
        )) {
            $this->logger->warning(
                sprintf(
                    'You have changed db configuration that not compatible with %s slave connection.',
                    DbConfig::CONNECTION_DEFAULT
                ),
                ['errorCode' => Error::WARN_DB_CONFIG_NOT_COMPATIBLE_WITH_SLAVE]
            );
        } elseif (!empty($dbConfig[DbConfig::KEY_SLAVE_CONNECTION][DbConfig::CONNECTION_DEFAULT])) {
            $this->logger->info(sprintf('Set DB slave connection for %s connection.', DbConfig::CONNECTION_DEFAULT));
        } else {
            $this->logger->notice(sprintf(
                'Enabling of the variable MYSQL_USE_SLAVE_CONNECTION had no effect for %s connection, ' .
                'because %s slave connection is not configured on your environment.',
                DbConfig::CONNECTION_DEFAULT,
                DbConfig::CONNECTION_DEFAULT
            ));
        }
    }

    /**
     * Returns config from app/etc/env.php
     *
     * @return array
     * @throws FileSystemException
     */
    private function getMageConfigData(): array
    {
        if (null === $this->mageConfigData) {
            $this->mageConfigData = $this->configReader->read();
        }
        return $this->mageConfigData;
    }

    /**
     * Returns database configurations as merge result of customer configurations from .magento.env.yaml
     * and connection data from cloud environment.
     *
     * @return array
     * @throws ConfigException
     */
    private function getDbConfigData(): array
    {
        if (null === $this->dbConfigData) {
            $this->dbConfigData = $this->dbConfig->get();
        }
        return $this->dbConfigData;
    }

    /**
     * Checks availability slave connections
     *
     * @return bool
     * @throws ConfigException
     */
    private function slaveIsAvailable(): bool
    {
        return isset($this->getDbConfigData()[DbConfig::KEY_SLAVE_CONNECTION]);
    }

    /**
     * Returns the configuration of split connections from the file `app/etc/env.php`
     *
     * @return array
     * @throws FileSystemException
     */
    private function getMageSplitConnectionsConfig(): array
    {
        $existedSplitConnections = [];
        $mageConfig = $this->getMageConfigData();
        if (isset($mageConfig[DbConfig::KEY_DB][DbConfig::KEY_CONNECTION])) {
            $existedSplitConnections = array_intersect_key(
                $mageConfig[DbConfig::KEY_DB][DbConfig::KEY_CONNECTION],
                array_flip(DbConfig::SPLIT_CONNECTIONS)
            );
        }
        return $existedSplitConnections;
    }
}
