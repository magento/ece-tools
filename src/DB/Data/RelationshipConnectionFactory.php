<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\DB\Data;

use Magento\MagentoCloud\Config\Environment;

/**
 * Responsible for creating and configuring Magento\MagentoCloud\DB\Data\ConnectionInterface instances.
 */
class RelationshipConnectionFactory
{
    const CONNECTION_MAIN = 'main';
    const CONNECTION_SLAVE = 'slave';

    /**
     * @var Environment
     */
    private $environment;

    /**
     * @param Environment $environment
     */
    public function __construct(Environment $environment)
    {
        $this->environment = $environment;
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
                $connection = new RelationshipConnection($this->environment->getRelationship('database')[0] ?? []);
                break;
            case self::CONNECTION_SLAVE:
                $connection = new RelationshipConnection(
                    $this->environment->getRelationship('database-slave')[0] ?? []
                );
                break;
            default:
                throw new \RuntimeException(
                    sprintf('Connection with type %s doesn\'t exist', $connectionType)
                );
        }

        return $connection;
    }
}
