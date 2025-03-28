<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\DB\Data;

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
     * @var ConnectionTypes
     */
    private $connectionType;

    /**
     * @param ConnectionTypes $connectionType
     */
    public function __construct(ConnectionTypes $connectionType)
    {
        $this->connectionType = $connectionType;
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
                $configuration = $this->connectionType->getConfiguration();
                break;
            case self::CONNECTION_SLAVE:
                $configuration = $this->connectionType->getSlaveConfiguration();
                break;
            case self::CONNECTION_QUOTE_MAIN:
                $configuration = $this->connectionType->getQuoteConfiguration();
                break;
            case self::CONNECTION_QUOTE_SLAVE:
                $configuration = $this->connectionType->getQuoteSlaveConfiguration();
                break;
            case self::CONNECTION_SALES_MAIN:
                $configuration = $this->connectionType->getSalesConfiguration();
                break;
            case self::CONNECTION_SALES_SLAVE:
                $configuration = $this->connectionType->getSalesSlaveConfiguration();
                break;
            default:
                throw new \RuntimeException(
                    sprintf('Connection with type %s does not exist', $connectionType)
                );
        }

        return new RelationshipConnection($configuration);
    }
}
