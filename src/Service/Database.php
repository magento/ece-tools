<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Service;

use Magento\MagentoCloud\DB\ConnectionInterface;
use Magento\MagentoCloud\DB\Data\ConnectionFactory;
use Magento\MagentoCloud\DB\Data\ConnectionTypes;

/**
 * Returns main database service configurations.
 */
class Database implements ServiceInterface
{
    /**
     * @var ConnectionTypes
     */
    private $connectionType;

    /**
     * @var ConnectionInterface
     */
    private $connection;

    /**
     * @var string
     */
    private $version;

    /**
     * @var ConnectionFactory
     */
    private $connectionFactory;

    /**
     * @param ConnectionTypes $connectionType
     * @param ConnectionInterface $connection
     * @param ConnectionFactory $connectionFactory
     */
    public function __construct(
        ConnectionTypes $connectionType,
        ConnectionInterface $connection,
        ConnectionFactory $connectionFactory
    ) {
        $this->connectionType = $connectionType;
        $this->connection = $connection;
        $this->connectionFactory = $connectionFactory;
    }

    /**
     * Returns database configuration from relationships
     *
     * @inheritdoc
     */
    public function getConfiguration(): array
    {
        return $this->connectionType->getConfiguration();
    }

    /**
     * Retrieves MySQL service version from relationship configuration if 'type' contains version
     * and 'host' is relevant with final connection data.
     * Otherwise using SQL query.
     *
     * {@inheritDoc}
     */
    public function getVersion(): string
    {
        if ($this->version === null) {
            $this->version = '0';

            try {
                $relationshipConfig = $this->getConfiguration();
                $connectionData = $this->connectionFactory->create(ConnectionFactory::CONNECTION_MAIN);

                if (isset($relationshipConfig['host'])
                    && $relationshipConfig['host'] == $connectionData->getHost()
                    && isset($relationshipConfig['type']) && strpos($relationshipConfig['type'], ':') !== false
                ) {
                    $this->version = explode(':', $relationshipConfig['type'])[1];
                } else {
                    $rawVersion = $this->connection->selectOne('SELECT VERSION() as version');
                    preg_match('/^\d+\.\d+/', $rawVersion['version'] ?? '', $matches);

                    $this->version = $matches[0] ?? '0';
                }
            } catch (\Exception $e) {
                throw new ServiceException($e->getMessage());
            }
        }

        return $this->version;
    }
}
