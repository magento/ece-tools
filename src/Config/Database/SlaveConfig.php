<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Config\Database;

use Magento\MagentoCloud\DB\Data\ConnectionInterface;

/**
 * Returns mysql slave connection
 */
class SlaveConfig implements ConfigInterface
{
    /**
     * @var ConnectionInterface
     */
    private $connectionData;

    /**
     * @param ConnectionInterface $connectionData
     */
    public function __construct(ConnectionInterface $connectionData)
    {
        $this->connectionData = $connectionData;
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

        if ($this->connectionData->getHost()) {
            $host = $this->connectionData->getHost();

            if (!empty($this->connectionData->getPort())) {
                $host .= ':' . $this->connectionData->getPort();
            }

            $slaveConnection = [
                'host' => $host,
                'username' => $this->connectionData->getUser(),
                'dbname' => $this->connectionData->getDBName(),
                'password' => $this->connectionData->getPassword(),
                'model' => 'mysql4',
                'engine' => 'innodb',
                'initStatements' => 'SET NAMES utf8;',
                'active' => '1',
            ];
        }

        return $slaveConnection;
    }
}
