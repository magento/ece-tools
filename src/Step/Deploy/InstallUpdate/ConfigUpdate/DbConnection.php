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

    const SPLIT_DB_CONNECTION_MAP = [
        DbConfig::CONNECTION_SALE => DeployInterface::VAL_SPLIT_DB_SALE,
        DbConfig::CONNECTION_CHECKOUT => DeployInterface::VAL_SPLIT_DB_QUOTE,
    ];

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
    )
    {
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
                . ' Will be applied the previous database configuration');
            return;
        }

        $this->logger->info('Updating env.php DB connection configuration.');

        $config = $this->configReader->read();

        $differentMainConnections = array_diff_assoc(
            $config[DbConfig::KEY_DB][DbConfig::KEY_CONNECTION][DbConfig::CONNECTION_DEFAULT],
            $dbConfig[DbConfig::KEY_CONNECTION][DbConfig::CONNECTION_DEFAULT]
        );

        $enabledSplitConnections = array_intersect_key(
            $config[DbConfig::KEY_DB][DbConfig::KEY_CONNECTION],
            array_flip(DbConfig::SPLIT_CONNECTIONS)
        );

        if ($differentMainConnections) {
            $this->logger->notice(
                'Database was already split but deploy was configured with new connection.'
                . ' The previous connection data will be ignored.'
            );
        }

        if (!$enabledSplitConnections || $differentMainConnections) {
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
            $resourceConfig = array_intersect_key(
                $this->resourceConfig->get(),
                array_flip([ResourceConfig::RESOURCE_DEFAULT_SETUP])
            );

            $config[DbConfig::KEY_DB] = $dbConfig;
            $config[ResourceConfig::KEY_RESOURCE] = $resourceConfig;
            $this->addLoggingAboutSlaveConnection($config[DbConfig::KEY_DB]);
            $this->configWriter->create($config);
            return;
        }

        $differentSplitConnections = !empty(array_diff_assoc(
            array_intersect_key($dbConfig[DbConfig::KEY_CONNECTION], array_flip(DbConfig::SPLIT_CONNECTIONS)),
            $enabledSplitConnections
        ));

        if ($differentSplitConnections) {
            $this->logger->warning("For split databases used custom connections.");
            $this->flagManager->set(FlagManager::FLAG_IGNORE_SPLIT_DB);
            return;
        }

        $varSplitDb = $this->stageConfig->get(DeployInterface::VAR_SPLIT_DB);
        foreach (array_keys($enabledSplitConnections) as $enabledSplitConnection) {
            $type = self::SPLIT_DB_CONNECTION_MAP[$enabledSplitConnection];
            if (!in_array($type, $varSplitDb)) {
                $this->logger->warning("Db $type  was split before, but SPLIT_DB does not have this info");
            }
        }
    }

    /**
     * Adds logging about slave connection.
     * @param array $dbConfig
     */
    private function addLoggingAboutSlaveConnection(array $dbConfig)
    {
        $envDbConfig = $this->stageConfig->get(DeployInterface::VAR_DATABASE_CONFIGURATION);
        $isUseSlave = $this->stageConfig->get(DeployInterface::VAR_MYSQL_USE_SLAVE_CONNECTION);
        $isMergeRequired = !$this->configMerger->isEmpty($envDbConfig)
            && !$this->configMerger->isMergeRequired($envDbConfig);
        $connectionNames = array_keys($dbConfig[' connection']);
        foreach ($connectionNames as $connectionName) {
            $serviceConnectionName = DbConfig::CONNECTION_MAP['connection'][$connectionName];
            $serviceConnectionData = $this->connectionFactory->create($serviceConnectionName);
            if (!$serviceConnectionData->getHost() || !$isUseSlave || $isMergeRequired) {
                continue;
            } elseif (!$this->dbConfig->isDbConfigCompatibleWithSlaveConnection($connectionName)) {
                $this->logger->warning(sprintf(
                    'You have changed db configuration that not compatible with %s slave connection.',
                    $connectionName
                ));
            } elseif (!empty($config['slave_connection'][$connectionName])) {
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
