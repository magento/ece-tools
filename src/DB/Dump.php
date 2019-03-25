<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\DB;

use Magento\MagentoCloud\DB\Data\ConnectionFactory;

/**
 * Class Dump generate mysqldump command with read only connection
 */
class Dump implements DumpInterface
{
    /**
     * Factory for creation database data connection classes
     *
     * @var ConnectionFactory
     */
    private $connectionFactory;

    /**
     * @param ConnectionFactory $connectionFactory
     */
    public function __construct(
        ConnectionFactory $connectionFactory
    ) {
        $this->connectionFactory = $connectionFactory;
    }

    /**
     * Returns mysqldump command for executing in shell.
     *
     * {@inheritdoc}
     */
    public function getCommand(): string
    {
        $connectionData = $this->connectionFactory->create(ConnectionFactory::CONNECTION_SLAVE);
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
