<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\DB;

use Magento\MagentoCloud\DB\Data\ConnectionFactory;
use Magento\MagentoCloud\DB\Data\ConnectionInterface as DatabaseConnectionInterface;

/**
 * Class Dump generate mysqldump command with read only connection
 */
class Dump implements DumpInterface
{
    /**
     * Database connection data for read operations
     *
     * @var DatabaseConnectionInterface
     */
    private $connectionData;

    /**
     * @param ConnectionFactory $connectionFactory
     */
    public function __construct(
        ConnectionFactory $connectionFactory
    ) {
        $this->connectionData = $connectionFactory->create(ConnectionFactory::CONNECTION_SLAVE);
    }

    /**
     * Returns mysqldump command for executing in shell.
     *
     * {@inheritdoc}
     */
    public function getCommand(): string
    {
        $command = 'mysqldump -h ' . escapeshellarg($this->connectionData->getHost())
            . ' -u ' . escapeshellarg($this->connectionData->getUser());

        $port = $this->connectionData->getPort();
        if (!empty($port)) {
            $command .= ' -P ' . escapeshellarg($port);
        }

        $password = $this->connectionData->getPassword();
        if ($password) {
            $command .= ' -p' . escapeshellarg($password);
        }
        $command .= ' ' . escapeshellarg($this->connectionData->getDbName())
            . ' --single-transaction --no-autocommit --quick';

        return $command;
    }
}
