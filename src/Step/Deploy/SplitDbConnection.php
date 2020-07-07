<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Step\Deploy;

use Magento\MagentoCloud\App\Error;
use Magento\MagentoCloud\App\GenericException;
use Magento\MagentoCloud\Config\Database\DbConfig;
use Magento\MagentoCloud\Config\Magento\Env\ReaderInterface as ConfigReader;
use Magento\MagentoCloud\Config\Stage\DeployInterface;
use Magento\MagentoCloud\Filesystem\Flag\Manager as FlagManager;
use Magento\MagentoCloud\Shell\MagentoShell;
use Magento\MagentoCloud\Shell\ShellException;
use Magento\MagentoCloud\Step\Deploy\SplitDbConnection\SlaveConnection;
use Magento\MagentoCloud\Step\StepException;
use Magento\MagentoCloud\Step\StepInterface;
use Magento\MagentoCloud\Util\UpgradeProcess;
use Psr\Log\LoggerInterface;

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
     * @var UpgradeProcess
     */
    private $upgradeProcess;

    /**
     * @var SlaveConnection
     */
    private $slaveConnection;

    /**
     * @param DeployInterface $stageConfig
     * @param DbConfig $dbConfig
     * @param LoggerInterface $logger
     * @param FlagManager $flagManager
     * @param ConfigReader $configReader
     * @param MagentoShell $magentoShell
     * @param UpgradeProcess $upgradeProcess
     * @param SlaveConnection $slaveConnection
     */
    public function __construct(
        DeployInterface $stageConfig,
        DbConfig $dbConfig,
        LoggerInterface $logger,
        FlagManager $flagManager,
        ConfigReader $configReader,
        MagentoShell $magentoShell,
        UpgradeProcess $upgradeProcess,
        SlaveConnection $slaveConnection
    ) {
        $this->stageConfig = $stageConfig;
        $this->dbConfig = $dbConfig;
        $this->logger = $logger;
        $this->flagManager = $flagManager;
        $this->configReader = $configReader;
        $this->magentoShell = $magentoShell;
        $this->upgradeProcess = $upgradeProcess;
        $this->slaveConnection = $slaveConnection;
    }

    /**
     * Starts the database splitting process
     * Updates the configuration of slave connections for split connections
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
            $this->logger->warning(
                sprintf(
                    'Enabling a split database will be skipped.'
                    . ' Relationship do not have configuration for next types: %s',
                    implode(', ', $notAvailableSplitTypes)
                ),
                ['errorCode' => Error::WARN_SPLIT_DB_ENABLING_SKIPPED]
            );
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
                'The SPLIT_DB variable is missing the configuration for split connection types: '
                . implode(', ', $missedSplitTypes),
                ['errorCode' => Error::WARN_NOT_ENOUGH_DATA_SPLIT_DB_VAR]
            );
            return;
        }
        if (!empty($splitTypes)) {
            $this->enableSplitConnections(array_diff($splitTypes, $enabledSplitTypes), $dbConfig);
        }
        $this->slaveConnection->update();
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
     * @throws StepException
     */
    private function enableSplitConnections(array $types, array $dbConfig)
    {
        try {
            $splitTypeMap = array_flip(self::SPLIT_CONNECTION_MAP);
            foreach ($types as $type) {
                $connectionConfig = $dbConfig[DbConfig::KEY_CONNECTION][$splitTypeMap[$type]];
                $cmd = $this->buildSplitDbCommand($type, $connectionConfig);
                $this->magentoShell->execute($cmd);
                $this->logger->info(sprintf(
                    '%s tables were split to DB %s in %s',
                    ucfirst($type),
                    $connectionConfig['dbname'],
                    $connectionConfig['host']
                ));
                $this->upgradeProcess->execute();
            }
        } catch (ShellException $e) {
            throw new StepException($e->getMessage(), Error::DEPLOY_SPLIT_DB_COMMAND_FAILED, $e);
        } catch (GenericException $e) {
            throw new StepException($e->getMessage(), $e->getCode(), $e);
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
}
