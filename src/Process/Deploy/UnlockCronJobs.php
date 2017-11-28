<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Process\Deploy;

use Magento\MagentoCloud\DB\ConnectionInterface;
use Magento\MagentoCloud\Process\ProcessInterface;
use Psr\Log\LoggerInterface;

/**
 * Updates running cron jobs.
 */
class UnlockCronJobs implements ProcessInterface
{
    const STATUS_RUNNING = 'running';
    const STATUS_MISSED = 'missed';

    /**
     * @var ConnectionInterface
     */
    private $connection;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param ConnectionInterface $connection
     * @param LoggerInterface $logger
     */
    public function __construct(
        ConnectionInterface $connection,
        LoggerInterface $logger
    ) {
        $this->connection = $connection;
        $this->logger = $logger;
    }

    /**
     * Updates running cron jobs to status 'missed'.
     *
     * {@inheritdoc}
     */
    public function execute()
    {
        $updateCronStatusQuery = 'UPDATE `cron_schedule` SET `status` = :to_status WHERE `status` = :from_status';

        $updatedJobsCount = $this->connection->affectingQuery(
            $updateCronStatusQuery,
            [
                ':to_status' => self::STATUS_MISSED,
                ':from_status' => self::STATUS_RUNNING
            ]
        );

        if ($updatedJobsCount) {
            $this->logger->info(
                sprintf(
                    '%d cron jobs were updated from status "%s" to status "%s"',
                    $updatedJobsCount,
                    self::STATUS_RUNNING,
                    self::STATUS_MISSED
                )
            );
        }
    }
}
