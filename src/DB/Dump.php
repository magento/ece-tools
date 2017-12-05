<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\DB;

use Magento\MagentoCloud\DB\Data\ConnectionInterface;

/**
 * Class Dump generate mysqldump command with read only connection
 */
class Dump implements DumpInterface
{
    /**
     * Database connection data for read operations
     *
     * @var ConnectionInterface
     */
    private $connectionData;

    /**
     * @param ConnectionInterface $connectionData
     */
    public function __construct(
        ConnectionInterface $connectionData
    ) {
        $this->connectionData = $connectionData;
    }

    /**
     * Returns mysqldump command for executing in shell.
     *
     * {@inheritdoc}
     */
    public function getCommand(): string
    {
        $command = 'mysqldump -h ' . escapeshellarg($this->connectionData->getHost())
            . ' -P ' . escapeshellarg($this->connectionData->getPort())
            . ' -u ' . escapeshellarg($this->connectionData->getUser());
        $password = $this->connectionData->getPassword();
        if ($password) {
            $command .= ' -p' . escapeshellarg($password);
        }
        $command .= ' ' . escapeshellarg($this->connectionData->getDbName())
            . ' --single-transaction --no-autocommit --quick';

        return $command;
    }
}
