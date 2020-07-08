<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\DB\Data;

use Magento\MagentoCloud\Service\Database;

/**
 * Responsible for creating and configuring Magento\MagentoCloud\DB\Data\ConnectionInterface instances.
 */
class RelationshipConnectionFactory
{
    const CONNECTION_MAIN = 'main';
    const CONNECTION_SLAVE = 'slave';

    const CONNECTION_QUOTE_MAIN = 'quote-main';
    const CONNECTION_QUOTE_SLAVE = 'quote-slave';

    const CONNECTION_SALES_MAIN = 'sales-main';
    const CONNECTION_SALES_SLAVE = 'sales-slave';

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
                $configuration = $this->database->getConfiguration();
                break;
            case self::CONNECTION_SLAVE:
                $configuration = $this->database->getSlaveConfiguration();
                break;
            case self::CONNECTION_QUOTE_MAIN:
                $configuration = $this->database->getQuoteConfiguration();
                break;
            case self::CONNECTION_QUOTE_SLAVE:
                $configuration = $this->database->getQuoteSlaveConfiguration();
                break;
            case self::CONNECTION_SALES_MAIN:
                $configuration = $this->database->getSalesConfiguration();
                break;
            case self::CONNECTION_SALES_SLAVE:
                $configuration = $this->database->getSalesSlaveConfiguration();
                break;
            default:
                throw new \RuntimeException(
                    sprintf('Connection with type %s does not exist', $connectionType)
                );
        }

        return new RelationshipConnection($configuration);
    }
}
