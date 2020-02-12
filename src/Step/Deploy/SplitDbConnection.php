<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Step\Deploy;

use Magento\MagentoCloud\Config\ConfigException;
use Magento\MagentoCloud\Filesystem\FileSystemException;
use Magento\MagentoCloud\Filesystem\Flag\ConfigurationMismatchException;
use Magento\MagentoCloud\Shell\MagentoShell;
use Magento\MagentoCloud\Step\StepInterface;
use Psr\Log\LoggerInterface;
use Magento\MagentoCloud\Filesystem\Flag\Manager as FlagManager;
use Magento\MagentoCloud\Config\Stage\DeployInterface;
use Magento\MagentoCloud\Config\Database\DbConfig;
use Magento\MagentoCloud\Config\Database\ResourceConfig;
use Magento\MagentoCloud\Config\Magento\Env\ReaderInterface as ConfigReader;
use Magento\MagentoCloud\Config\Magento\Env\WriterInterface as ConfigWriter;

/**
 * Enables split database
 */
class SplitDbConnection implements StepInterface
{
    /**
     * Types of split database
     */
    const SPLIT_CONNECTION_MAP = [
        DbConfig::CONNECTION_SALES => DeployInterface::SPLIT_DB_VALUE_SALES,
        DbConfig::CONNECTION_CHECKOUT => DeployInterface::SPLIT_DB_VALUE_QUOTE,
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
     * @var ConfigWriter
     */
    private $configWriter;

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
     * @param ConfigWriter $configWriter
     * @param MagentoShell $magentoShell
     */
    public function __construct(
        DeployInterface $stageConfig,
        DbConfig $dbConfig,
        ResourceConfig $resourceConfig,
        LoggerInterface $logger,
        FlagManager $flagManager,
        ConfigReader $configReader,
        ConfigWriter $configWriter,
        MagentoShell $magentoShell
    ) {
        $this->stageConfig = $stageConfig;
        $this->dbConfig = $dbConfig;
        $this->resourceConfig = $resourceConfig;
        $this->logger = $logger;
        $this->flagManager = $flagManager;
        $this->configReader = $configReader;
        $this->configWriter = $configWriter;
        $this->magentoShell = $magentoShell;
    }

    /**
     * Starts the database splitting process
     * Updates the configuration of slave connections for split connections
     *
     * @throws ConfigException
     * @throws FileSystemException
     * @throws ConfigurationMismatchException
     */
    public function execute()
    {
        if ($this->flagManager->exists(FlagManager::FLAG_IGNORE_SPLIT_DB)) {
            $this->logger->info(sprintf(
                'Enabling a split database will be skipped. The flag %s was detected.',
                FlagManager::FLAG_IGNORE_SPLIT_DB
            ));
            $this->flagManager->delete(FlagManager::FLAG_IGNORE_SPLIT_DB);
            return;
        }

        $splitTypes = $this->stageConfig->get(DeployInterface::VAR_SPLIT_DB);

        $dbConfig = $this->dbConfig->get();
        $notAvailableSplitTypes = $this->getMissedSplitTypes(
            $splitTypes,
            $dbConfig[DbConfig::KEY_CONNECTION] ?? []
        );

        if (!empty($notAvailableSplitTypes)) {
            $this->logger->error(sprintf(
                'Enabling a split database will be skipped.'
                . ' Relationship do not have configuration for next types: %s',
                implode(', ', $notAvailableSplitTypes)
            ));
            return;
        }

        $mageConfig = $this->configReader->read();

        $enabledSplitTypes = array_values(array_intersect_key(
            self::SPLIT_CONNECTION_MAP,
            $this->getSplitConnectionsConfig($mageConfig[DbConfig::KEY_DB][DbConfig::KEY_CONNECTION])
        ));

        $missedSplitTypes = array_diff($enabledSplitTypes, $splitTypes);

        if (!empty($missedSplitTypes)) {
            $this->logger->warning(
                'Variable SPLIT_DB does not have data which were already split types: '
                . implode(', ', $missedSplitTypes)
            );
            return;
        }
        if (!empty($splitTypes)) {
            $this->enableSplitConnections(array_diff($splitTypes, $enabledSplitTypes), $dbConfig);
        }
        $this->updateSlaveConnections($dbConfig);
    }

    /**
     * Returns split types that do not exist in $connectionsConfig but exists in $splitTypes
     *
     * @param array $splitTypes
     * @param array $connectionsConfig
     * @return array
     */
    private function getMissedSplitTypes(array $splitTypes, array $connectionsConfig): array
    {
        return array_values(array_diff_key(
            array_intersect(self::SPLIT_CONNECTION_MAP, $splitTypes),
            $this->getSplitConnectionsConfig($connectionsConfig)
        ));
    }

    /**
     * Returns configurations of split connections
     *
     * @param array $connectionsConfig
     * @return array
     */
    private function getSplitConnectionsConfig(array $connectionsConfig): array
    {
        return array_intersect_key($connectionsConfig, array_flip(DbConfig::SPLIT_CONNECTIONS));
    }

    /**
     * Enables split database
     *
     * @param array $types
     * @param array $dbConfig
     */
    private function enableSplitConnections(array $types, array $dbConfig)
    {
        $splitTypeMap = array_flip(self::SPLIT_CONNECTION_MAP);
        foreach ($types as $type) {
            $connectionConfig = $dbConfig[DbConfig::KEY_CONNECTION][$splitTypeMap[$type]];
            $cmd = $this->buildSplitDbCommand($type, $connectionConfig);
            $this->logger->debug($this->magentoShell->execute($cmd)->getOutput());
            $this->logger->info(sprintf(
                '%s tables were split to DB %s in %s',
                ucfirst($type),
                $connectionConfig['dbname'],
                $connectionConfig['host']
            ));
        }
    }

    /**
     * Returns Magento CLI for split database
     *
     * @param string $type
     * @param array $connectionConfig
     * @return string
     */
    private function buildSplitDbCommand(string $type, array $connectionConfig): string
    {
        return sprintf(
            'setup:db-schema:split-%s --host="%s" --dbname="%s" --username="%s" --password="%s"',
            $type,
            $connectionConfig['host'],
            $connectionConfig['dbname'],
            $connectionConfig['username'],
            $connectionConfig['password']
        );
    }

    /**
     * Updates slave connections
     *
     * @param array $dbConfig
     * @throws FileSystemException
     * @throws ConfigException
     */
    private function updateSlaveConnections(array $dbConfig)
    {
        if (!$this->stageConfig->get(DeployInterface::VAR_MYSQL_USE_SLAVE_CONNECTION)) {
            return;
        }
        $mageConfig = $this->configReader->read();
        $mageConnectionsConfig = $mageConfig[DbConfig::KEY_DB][DbConfig::KEY_CONNECTION];
        foreach (array_keys($this->getSplitConnectionsConfig($mageConnectionsConfig)) as $mageConnectionName) {
            if (!isset($dbConfig[DbConfig::KEY_SLAVE_CONNECTION][$mageConnectionName])) {
                $this->logger->warning(sprintf(
                    'Slave connection for %s connection not set. '
                    . 'Relationships do not have the configuration for this slave connection',
                    $mageConnectionName
                ));
                continue;
            }
            $connectionConfig = $dbConfig[DbConfig::KEY_SLAVE_CONNECTION][$mageConnectionName];
            $mageConfig[DbConfig::KEY_DB][DbConfig::KEY_SLAVE_CONNECTION][$mageConnectionName] = $connectionConfig;
            $this->logger->info(sprintf('Slave connection for %s connection was set', $mageConnectionName));
        }
        $this->configWriter->create($mageConfig);
    }
}
