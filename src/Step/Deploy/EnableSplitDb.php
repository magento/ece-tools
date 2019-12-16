<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Step\Deploy;

use Magento\MagentoCloud\Shell\MagentoShell;
use Magento\MagentoCloud\Step\StepInterface;
use Psr\Log\LoggerInterface;
use Magento\MagentoCloud\Filesystem\Flag\Manager as FlagManager;
use Magento\MagentoCloud\Config\Stage\DeployInterface;
use Magento\MagentoCloud\Config\Database\DbConfig;
use Magento\MagentoCloud\Config\Database\ResourceConfig;
use Magento\MagentoCloud\Config\Magento\Env\ReaderInterface as ConfigReader;

/**
 * Enables split database
 */
class EnableSplitDb implements StepInterface
{
    /**
     * Types of split database
     */
    const TYPE_MAP = [
        DbConfig::CONNECTION_SALE => DeployInterface::VAL_SPLIT_DB_SALES,
        DbConfig::CONNECTION_CHECKOUT => DeployInterface::VAL_SPLIT_DB_QUOTE,
    ];

    /**
     * @var LoggerInterface
     */
    private $logger;

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
     * @var FlagManager
     */
    private $flagManager;

    /**
     * @var ConfigReader
     */
    private $configReader;

    /**
     * @var MagentoShell
     */
    private $magentoShell;

    /**
     * @param DeployInterface $stageConfig
     * @param DbConfig $dbConfig
     * @param ResourceConfig $resourceConfig
     * @param LoggerInterface $logger
     * @param FlagManager $flagManager
     * @param ConfigReader $configReader
     * @param MagentoShell $magentoShell
     */
    public function __construct(
        DeployInterface $stageConfig,
        DbConfig $dbConfig,
        ResourceConfig $resourceConfig,
        LoggerInterface $logger,
        FlagManager $flagManager,
        ConfigReader $configReader,
        MagentoShell $magentoShell
    ) {
        $this->stageConfig = $stageConfig;
        $this->dbConfig = $dbConfig;
        $this->resourceConfig = $resourceConfig;
        $this->logger = $logger;
        $this->flagManager = $flagManager;
        $this->configReader = $configReader;
        $this->magentoShell = $magentoShell;
    }

    /**
     * @inheritdoc
     */
    public function execute()
    {
        if ($this->flagManager->exists(FlagManager::FLAG_IGNORE_SPLIT_DB)) {
            $this->flagManager->delete(FlagManager::FLAG_IGNORE_SPLIT_DB);
            return;
        }
        $valSplitDb = $this->stageConfig->get(DeployInterface::VAR_SPLIT_DB);
        if (empty($valSplitDb)) {
            return;
        }
        $dbConfig = $this->dbConfig->get();
        if (empty(array_intersect(
            array_keys($dbConfig[DbConfig::KEY_CONNECTION]),
            DbConfig::SPLIT_CONNECTIONS
        ))) {
            $this->logger->error("Split Db will skipped. Relationship do not have split connections");
            return;
        }
        $config = $this->configReader->read();

        $enabledSplitConnections = array_intersect_key(
            $config[DbConfig::KEY_DB][DbConfig::KEY_CONNECTION],
            array_flip(DbConfig::SPLIT_CONNECTIONS)
        );
        $enabledSplitTypesMap = array_intersect_key(self::TYPE_MAP, $enabledSplitConnections);
        $missedSplitTypes = array_diff($enabledSplitTypesMap, $valSplitDb);

        if (!empty($enabledSplitConnections) && empty($missedSplitTypes)) {
            return;
        } elseif (!empty($missedSplitTypes)) {
            $this->logger->warning(
                'Variable SPLIT_DB does not have data which was already split: '
                . implode(',', $missedSplitTypes)
            );
            return;
        }
        $useSlave = $this->stageConfig->get(DeployInterface::VAR_MYSQL_USE_SLAVE_CONNECTION);
        $splitDbTypeMap = array_flip(self::TYPE_MAP);
        foreach (array_diff($valSplitDb, $enabledSplitTypesMap) as $type) {
            $connectionName = $splitDbTypeMap[$type];
            $splitDbConfig = $dbConfig[DbConfig::KEY_CONNECTION][$connectionName];
            $cmd = sprintf(
                'setup:db-schema:split-%s --host="%s" --dbname="%s" --username="%s" --password="%s"',
                $type,
                $splitDbConfig['host'],
                $splitDbConfig['dbname'],
                $splitDbConfig['username'],
                $splitDbConfig['password']
            );
            $outputCmd = $this->magentoShell->execute($cmd)->getOutput();
            $this->logger->debug($outputCmd);
            $this->logger->info(sprintf(
                'Quote tables were split to DB %s in %s',
                $splitDbConfig['dbname'],
                $splitDbConfig['host']
            ));

            if ($useSlave) {
                $splitDbConfigSlave = $dbConfig[DbConfig::KEY_SLAVE_CONNECTION][$connectionName];
                $resourceName = ResourceConfig::RESOURCE_MAP[$connectionName];
                $cmd = sprintf(
                    'setup:db-schema:add-slave --host="%s" --dbname="%s" --username="%s" --password="%s"'
                    . ' --connection="%s" --resource="%s"',
                    $splitDbConfigSlave['host'],
                    $splitDbConfigSlave['dbname'],
                    $splitDbConfigSlave['username'],
                    $splitDbConfigSlave['password'],
                    $connectionName,
                    $resourceName
                );
                $outputCmd = $this->magentoShell->execute($cmd)->getOutput();
                $this->logger->debug($outputCmd);
            }
        }
    }
}
