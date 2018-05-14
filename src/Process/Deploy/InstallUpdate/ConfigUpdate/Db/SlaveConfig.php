<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Process\Deploy\InstallUpdate\ConfigUpdate\Db;

use Magento\MagentoCloud\DB\Data\ConnectionInterface;

/**
 * Returns mysql slave connection
 */
class SlaveConfig
{
    /**
     * @var ConnectionInterface
     */
    private $readConnection;

    /**
     * @param ConnectionInterface $readConnection
     */
    public function __construct(ConnectionInterface $readConnection)
    {
        $this->readConnection = $readConnection;
    }

    /**
     * Returns mysql slave connection if database configuration is present in relationships
     * otherwise returns empty array.
     *
     * @return array
     */
    public function get(): array
    {
        $slaveConnection = [];

        if ($this->readConnection->getHost()) {
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
