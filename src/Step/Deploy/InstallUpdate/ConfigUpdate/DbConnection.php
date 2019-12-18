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
use Magento\MagentoCloud\Step\Deploy\EnableSplitDb;
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
    private $connectionFactory;

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
     * @param RelationshipConnectionFactory $connectionFactory
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
        RelationshipConnectionFactory $connectionFactory,
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
        $this->connectionFactory = $connectionFactory;
        $this->flagManager = $flagManager;
    }

    /**
     * @inheritdoc
     */
    public function execute()
    {
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

        $this->logger->info('Updating env.php DB connection configuration.');

        $envConfig = $this->configReader->read();
        $envDbConfig = $envConfig[DbConfig::KEY_DB];

        $enabledSplitConnections = $this->getEnabledSplitConnections($envDbConfig[DbConfig::KEY_CONNECTION]);
        $isDifferentDefaultConnection = !$this->isSameConnection(
            $dbConfig[DbConfig::KEY_CONNECTION][DbConfig::CONNECTION_DEFAULT],
            $envDbConfig[DbConfig::KEY_CONNECTION][DbConfig::CONNECTION_DEFAULT]
        );

        if (!empty($enabledSplitConnections) && $isDifferentDefaultConnection) {
            $this->logger->notice(
                'Database was already split but deploy was configured with new connection.'
                . ' The previous connection data will be ignored.'
            );
        }

        if (empty($enabledSplitConnections) || (!empty($enabledSplitConnections) && $isDifferentDefaultConnection)) {
            $envConfig[DbConfig::KEY_DB] = $this->getOnlyMainDbConfig($dbConfig);
            $envConfig[ResourceConfig::KEY_RESOURCE] = $this->getMainResourceConfig();
            $this->addLoggingAboutSlaveConnection($envConfig[DbConfig::KEY_DB]);
            $this->configWriter->create($envConfig);
            return;
        }

        $customSplitConnections = $this->getCustomSplitConnections(
            $enabledSplitConnections,
            $dbConfig[DbConfig::KEY_CONNECTION]
        );
        if (!empty($customSplitConnections)) {
            $this->logger->warning(sprintf(
                'For split databases used custom connections: %s',
                implode(', ', $customSplitConnections)
            ));
            $this->flagManager->set(FlagManager::FLAG_IGNORE_SPLIT_DB);
            return;
        }

        $this->checkSplitConnections($enabledSplitConnections);
    }

    /**
     * @param array $enabledSplitConnections
     */
    private function checkSplitConnections(array $enabledSplitConnections)
    {
        $varSplitDb = $this->stageConfig->get(DeployInterface::VAR_SPLIT_DB);
        foreach (array_keys($enabledSplitConnections) as $connectionName) {
            $type = EnableSplitDb::TYPE_MAP[$connectionName];
            if (!in_array($type, $varSplitDb)) {
                $this->logger->warning(sprintf(
                    'Db %s was split before, but SPLIT_DB does not have this info',
                    $type
                ));
                return;
            }
        }
    }

    /**
     * Returns main database configuration
     *
     * @param array $dbConfig
     * @return array
     */
    private function getOnlyMainDbConfig(array $dbConfig): array
    {
        $dbConfig[DbConfig::KEY_CONNECTION] = array_intersect_key(
            $dbConfig[DbConfig::KEY_CONNECTION],
            array_flip(DbConfig::MAIN_CONNECTIONS)
        );
        if (isset($dbConfig[DbConfig::KEY_SLAVE_CONNECTION])) {
            $dbConfig[DbConfig::KEY_SLAVE_CONNECTION] = array_intersect_key(
                $dbConfig[DbConfig::KEY_SLAVE_CONNECTION],
                array_flip(DbConfig::MAIN_CONNECTIONS)
            );
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
     * Returns enabled custom split connections
     *
     * @param array $enabledSplitConnections
     * @param array $connections
     * @return array
     */
    private function getCustomSplitConnections(array $enabledSplitConnections, array $connections): array
    {
        $customSplitConnections = [];
        foreach ($enabledSplitConnections as $connectionName => $connectionData) {
            if (isset($connections[$connectionName])
                && !$this->isSameConnection($connections[$connectionName], $connectionData)) {
                $customSplitConnections[] = $connectionName;
            }
        }
        return $customSplitConnections;
    }

    /**
     * Returns enabled split connections
     *
     * @param array $envDbConnections
     * @return array
     */
    private function getEnabledSplitConnections(array $envDbConnections): array
    {
        return array_intersect_key($envDbConnections, array_flip(DbConfig::SPLIT_CONNECTIONS));
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
     * Adds logging about slave connection.
     *
     * @param array $dbConfig
     */
    private function addLoggingAboutSlaveConnection(array $dbConfig)
    {
        $envDbConfig = $this->stageConfig->get(DeployInterface::VAR_DATABASE_CONFIGURATION);
        $isUseSlave = $this->stageConfig->get(DeployInterface::VAR_MYSQL_USE_SLAVE_CONNECTION);
        $isMergeRequired = !$this->configMerger->isEmpty($envDbConfig)
            && !$this->configMerger->isMergeRequired($envDbConfig);
        $connectionMap = DbConfig::CONNECTION_MAP[DbConfig::KEY_CONNECTION];
        foreach (array_keys($dbConfig[DbConfig::KEY_CONNECTION]) as $connectionName) {
            $connectionData = $this->connectionFactory->create($connectionMap[$connectionName]);
            if (!$connectionData->getHost() || !$isUseSlave || $isMergeRequired) {
                continue;
            }
            $isDbConfigCompatibleWithSlaveConnection = $this->dbConfig->isDbConfigCompatibleWithSlaveConnection(
                $envDbConfig,
                $connectionName,
                $connectionData
            );
            if (!$isDbConfigCompatibleWithSlaveConnection) {
                $this->logger->warning(sprintf(
                    'You have changed db configuration that not compatible with % s slave connection.',
                    $connectionName
                ));
            } elseif (!empty($dbConfig[DbConfig::KEY_SLAVE_CONNECTION][$connectionName])) {
                $this->logger->info(sprintf('Set DB slave connection for %s connection.', $connectionName));
            } else {
                $this->logger->info(
                    'Enabling of the variable MYSQL_USE_SLAVE_CONNECTION had no effect ' .
                    'because slave connection is not configured on your environment.'
                );
            }
        }
    }
}
