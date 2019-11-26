<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Config;

use Magento\MagentoCloud\App\GenericException;
use Magento\MagentoCloud\Config\Magento\Env\ReaderInterface;
use Magento\MagentoCloud\Config\Magento\Env\WriterInterface;
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
     * @var ReaderInterface
     */
    private $reader;

    /**
     * @var WriterInterface
     */
    private $writer;

    /**
     * @param LoggerInterface $logger
     * @param ConnectionInterface $connection
     * @param ReaderInterface $reader
     * @param WriterInterface $writer
     */
    public function __construct(
        LoggerInterface $logger,
        ConnectionInterface $connection,
        ReaderInterface $reader,
        WriterInterface $writer
    ) {
        $this->logger = $logger;
        $this->connection = $connection;
        $this->reader = $reader;
        $this->writer = $writer;
    }

    /**
     * Verifies is Magento installed based on install date in env.php
     *
     * 1. from environment variables check if db exists and has tables
     * 2. check if core_config_data and setup_module tables exist
     * 3. check install date
     *
     * @return bool
     * @throws GenericException
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

        $data = $this->reader->read();
        if (isset($data['install']['date'])) {
            $this->logger->info('Magento was installed on ' . $data['install']['date']);

            return true;
        }

        $this->writer->update(['install' => ['date' => date('r')]]);

        return true;
    }
}
