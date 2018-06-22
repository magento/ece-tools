<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Process\Deploy\Config;

use Magento\MagentoCloud\Config\Log;
use Magento\MagentoCloud\DB\ConnectionInterface;
use Magento\MagentoCloud\Process\ProcessInterface;
use Psr\Log\LoggerInterface;

/**
 * Changes Magento syslog configuration.
 */
class Syslog implements ProcessInterface
{
    /**
     * @var Log
     */
    private $logConfig;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var ConnectionInterface
     */
    private $connection;

    /**
     * @param ConnectionInterface $connection
     * @param LoggerInterface $logger
     * @param Log $logConfig
     */
    public function __construct(ConnectionInterface $connection, LoggerInterface $logger, Log $logConfig)
    {
        $this->connection = $connection;
        $this->logger = $logger;
        $this->logConfig = $logConfig;
    }

    /**
     * @inheritdoc
     */
    public function execute()
    {
        if ($this->logConfig->has(Log::HANDLER_SYSLOG)) {
            $this->logger->info('Enabling syslog logging');

            $this->connection->affectingQuery(
                "UPDATE `core_config_data` SET `value` = 1 WHERE `path` = 'dev/syslog/syslog_logging'"
            );
        }
    }
}
