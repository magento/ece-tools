<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\DB\Data;

use Magento\MagentoCloud\Config\Database\DbConfig;

/**
 * Responsible for creating and configuring Magento\MagentoCloud\DB\Data\ConnectionInterface instances.
 */
class ConnectionFactory
{
    const CONNECTION_MAIN = 'main';
    const CONNECTION_SLAVE = 'slave';

    /**
     * @var DbConfig
     */
    private $dbConfig;

    /**
     * @param DbConfig $dbConfig
     */
    public function __construct(DbConfig $dbConfig)
    {
        $this->dbConfig = $dbConfig;
    }

    /**
     * Creates and configures instance for final connections after merging with env variables.
     *
     * @param string $connectionType
     * @return ConnectionInterface
     * @throws \RuntimeException
     */
    public function create(string $connectionType): ConnectionInterface
    {
        switch ($connectionType) {
            case self::CONNECTION_MAIN:
                $connectionData = $this->dbConfig->get()['connection']['default'] ?? [];
                $connection = new Connection($connectionData);
                break;
            case self::CONNECTION_SLAVE:
                $connectionData = $this->dbConfig->get()['slave_connection']['default']
                    ?? $this->dbConfig->get()['connection']['default']
                    ?? [];
                $connection = new Connection($connectionData);
                break;
            default:
                throw new \RuntimeException(
                    sprintf('Connection with type %s doesn\'t exist', $connectionType)
                );
        }

        return $connection;
    }
}
