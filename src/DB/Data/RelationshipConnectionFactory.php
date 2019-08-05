<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\DB\Data;

use Magento\MagentoCloud\Service\Database;

/**
 * Responsible for creating and configuring Magento\MagentoCloud\DB\Data\ConnectionInterface instances.
 */
class RelationshipConnectionFactory
{
    const CONNECTION_MAIN = 'main';
    const CONNECTION_SLAVE = 'slave';

    /**
     * @var Database
     */
    private $database;

    /**
     * @param Database $database
     */
    public function __construct(Database $database)
    {
        $this->database = $database;
    }

    /**
     * Creates and configures instances for connections from cloud relationships.
     *
     * @param string $connectionType
     * @return ConnectionInterface
     * @throws \RuntimeException
     */
    public function create(string $connectionType): ConnectionInterface
    {
        switch ($connectionType) {
            case self::CONNECTION_MAIN:
                $connection = new RelationshipConnection($this->database->getConfiguration());
                break;
            case self::CONNECTION_SLAVE:
                $connection = new RelationshipConnection($this->database->getSlaveConfiguration());
                break;
            default:
                throw new \RuntimeException(
                    sprintf('Connection with type %s doesn\'t exist', $connectionType)
                );
        }

        return $connection;
    }
}
