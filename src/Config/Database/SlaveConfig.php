<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Config\Database;

use Magento\MagentoCloud\DB\Data\RelationshipConnectionFactory;

/**
 * Returns mysql slave connection
 */
class SlaveConfig implements ConfigInterface
{
    /**
     * @var RelationshipConnectionFactory
     */
    private $connectionFactory;

    /**
     * @param RelationshipConnectionFactory $connectionFactory
     */
    public function __construct(RelationshipConnectionFactory $connectionFactory)
    {
        $this->connectionFactory = $connectionFactory;
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

        $connectionData = $this->connectionFactory->create(RelationshipConnectionFactory::CONNECTION_SLAVE);
        if ($connectionData->getHost()) {
            $host = $connectionData->getHost();

            if (!empty($connectionData->getPort())) {
                $host .= ':' . $connectionData->getPort();
            }

            $slaveConnection = [
                'host' => $host,
                'username' => $connectionData->getUser(),
                'dbname' => $connectionData->getDbName(),
                'password' => $connectionData->getPassword(),
                'model' => 'mysql4',
                'engine' => 'innodb',
                'initStatements' => 'SET NAMES utf8;',
                'active' => '1',
            ];
        }

        return $slaveConnection;
    }
}
