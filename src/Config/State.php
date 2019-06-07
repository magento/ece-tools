<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Config;

use Magento\MagentoCloud\App\GenericException;
use Magento\MagentoCloud\DB\ConnectionInterface;
use Psr\Log\LoggerInterface;

/**
 * Describes the application state.
 */
class State
{
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var ConnectionInterface
     */
    private $connection;

    /**
     * @param LoggerInterface $logger
     * @param ConnectionInterface $connection
     */
    public function __construct(
        LoggerInterface $logger,
        ConnectionInterface $connection
    ) {
        $this->logger = $logger;
        $this->connection = $connection;
    }

    /**
     * Verifies is Magento installed based on table existence in the database.
     *
     * 1. from environment variables check if db exists and has tables
     * 2. check if core_config_data and setup_module tables exist
     *
     * @return bool
     * @throws GenericException if database has tables but missed core_config_data or setup_module table
     */
    public function isInstalled(): bool
    {
        $this->logger->info('Checking if db exists and has tables');

        $output = $this->connection->listTables();

        if (!is_array($output) || count($output) <= 1) {
            return false;
        }

        if (!in_array('core_config_data', $output) || !in_array('setup_module', $output)) {
            throw new GenericException('Missing either core_config_data or setup_module table');
        }

        return true;
    }
}
