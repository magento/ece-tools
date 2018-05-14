<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Process\Deploy\InstallUpdate\ConfigUpdate\Db;

use Magento\MagentoCloud\Config\Stage\DeployInterface;
use Magento\MagentoCloud\DB\Data\ConnectionInterface;
use Psr\Log\LoggerInterface;

/**
 * Returns mysql slave connection
 */
class SlaveConfig
{
    /**
     * @var DeployInterface
     */
    private $stageConfig;

    /**
     * @var ConnectionInterface
     */
    private $readConnection;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param DeployInterface $stageConfig
     * @param ConnectionInterface $readConnection
     * @param LoggerInterface $logger
     */
    public function __construct(
        DeployInterface $stageConfig,
        ConnectionInterface $readConnection,
        LoggerInterface $logger
    ) {
        $this->stageConfig = $stageConfig;
        $this->readConnection = $readConnection;
        $this->logger = $logger;
    }

    /**
     * Returns mysql read connection if MYSQL_USE_SLAVE_CONNECTION is enabled otherwise returns empty array.
     *
     * @return array
     */
    public function get(): array
    {
        $slaveConnection = [];
        if ($this->stageConfig->get(DeployInterface::VAR_MYSQL_USE_SLAVE_CONNECTION)
            && $this->readConnection->getHost()
        ) {
            $this->logger->info('Set DB slave connection.');

            $slaveConnection = [
                'host' => $this->readConnection->getHost() . ':' . $this->readConnection->getPort(),
                'username' => $this->readConnection->getUser(),
                'dbname' => $this->readConnection->getDBName(),
                'password' => $this->readConnection->getPassword(),
                'model' => 'mysql4',
                'engine' => 'innodb',
                'initStatements' => 'SET NAMES utf8;',
                'active' => '1',
            ];
        }

        return $slaveConnection;
    }
}
