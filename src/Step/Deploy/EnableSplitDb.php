<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\MagentoCloud\Step\Deploy;

use Magento\MagentoCloud\Step\Deploy\InstallUpdate\ConfigUpdate\DbConnection;
use Magento\MagentoCloud\Step\StepInterface;
use Psr\Log\LoggerInterface;
use Magento\MagentoCloud\Filesystem\Flag\Manager as FlagManager;
use Magento\MagentoCloud\Config\Stage\DeployInterface;
use Magento\MagentoCloud\Config\Database\DbConfig;
use Magento\MagentoCloud\Config\Database\ResourceConfig;
use Magento\MagentoCloud\Config\Magento\Env\ReaderInterface as ConfigReader;


class EnableSplitDb implements StepInterface
{
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

    public function __construct(
        DeployInterface $stageConfig,
        DbConfig $dbConfig,
        ResourceConfig $resourceConfig,
        LoggerInterface $logger,
        FlagManager $flagManager,
        ConfigReader $configReader
    )
    {
        $this->stageConfig = $stageConfig;
        $this->dbConfig = $dbConfig;
        $this->resourceConfig = $resourceConfig;
        $this->logger = $logger;
        $this->flagManager = $flagManager;
        $this->configReader = $configReader;
    }

    /**
     * @inheritdoc
     */
    public function execute()
    {
        if ($this->flagManager->exists(FlagManager::FLAG_IGNORE_SPLIT_DB)) {
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
        $enabledSplitTypesMap = array_intersect_key(DbConnection::SPLIT_DB_CONNECTION_MAP, $enabledSplitConnections);
        $missedSplitTypes = array_diff($enabledSplitTypesMap, $valSplitDb);

        if (!empty($enabledSplitConnections) && empty($missedSplitTypes)) {
            return;
        } elseif (!empty($missedSplitTypes)) {
            $this->logger->warning('Variable SPLIT_DB does not have data which was already split: ' . implode(',', $missedSplitTypes));
            return;
        }

        foreach (array_diff($valSplitDb, $enabledSplitTypesMap) as $type) {
            //do Split
        }

    }

}