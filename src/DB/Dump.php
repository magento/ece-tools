<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\DB;

use Magento\MagentoCloud\DB\Data\ConnectionInterface;

/**
 * Class Dump generate mysqldump command
 */
class Dump implements DumpInterface
{
    /**
     * Returns mysqldump command for executing in shell.
     *
     * {@inheritdoc}
     */
    public function getCommand(ConnectionInterface $connectionData): string
    {
        $command = 'mysqldump -h ' . escapeshellarg($connectionData->getHost())
            . ' -u ' . escapeshellarg($connectionData->getUser());

        $port = $connectionData->getPort();
        if (!empty($port)) {
            $command .= ' -P ' . escapeshellarg($port);
        }

        $password = $connectionData->getPassword();
        if ($password) {
            $command .= ' -p' . escapeshellarg($password);
        }
        $command .= ' ' . escapeshellarg($connectionData->getDbName())
            . ' --single-transaction --no-autocommit --quick';

        return $command;
    }
}
