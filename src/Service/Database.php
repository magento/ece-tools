<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Service;

use Magento\MagentoCloud\DB\ConnectionInterface;
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
     * @param ConnectionTypes $connectionType
     * @param ConnectionInterface $connection
     */
    public function __construct(
        ConnectionTypes $connectionType,
        ConnectionInterface $connection
    ) {
        $this->connectionType = $connectionType;
        $this->connection = $connection;
    }

    /**
     * @inheritdoc
     */
    public function getConfiguration(): array
    {
        return $this->connectionType->getConfiguration();
    }

    /**
     * Retrieves MySQL service version whether from relationship configuration
     * or using SQL query (for PRO environments)
     *
     * {@inheritDoc}
     */
    public function getVersion(): string
    {
        if ($this->version === null) {
            $this->version = '0';

            try {
                $databaseConfig = $this->getConfiguration();

                if (isset($databaseConfig['type']) && strpos($databaseConfig['type'], ':') !== false) {
                    $this->version = explode(':', $databaseConfig['type'])[1];
                } elseif (!empty($databaseConfig['host'])) {
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
