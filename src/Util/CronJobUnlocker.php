<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Util;

use Magento\MagentoCloud\DB\ConnectionInterface;

/**
 * Unlocks cron jobs stacked in status 'running'.
 */
class CronJobUnlocker
{
    const STATUS_RUNNING = 'running';
    const STATUS_MISSED = 'missed';

    /**
     * @var ConnectionInterface
     */
    private $connection;

    /**
     * @param ConnectionInterface $connection
     */
    public function __construct(ConnectionInterface $connection)
    {
        $this->connection = $connection;
    }

    /**
     * Moves all cron jobs from status running to status missed.
     *
     * @return int Count of updated rows.
     */
    public function unlockAll(): int
    {
        $updateCronStatusQuery = 'UPDATE `cron_schedule` SET `status` = :to_status WHERE `status` = :from_status';

        return $this->connection->affectingQuery(
            $updateCronStatusQuery,
            [
                ':to_status' => self::STATUS_MISSED,
                ':from_status' => self::STATUS_RUNNING
            ]
        );
    }

    /**
     * Moves cron jobs with given job_code from status running to status missed.
     *
     * @param string $jobCode Cron job code.
     * @return int Count of updated rows.
     */
    public function unlockByJobCode(string $jobCode): int
    {
        $updateCronStatusQuery = 'UPDATE `cron_schedule` SET `status` = :to_status WHERE `status` = :from_status'
            . ' AND `job_code` = :job_code';

        return $this->connection->affectingQuery(
            $updateCronStatusQuery,
            [
                ':to_status' => self::STATUS_MISSED,
                ':from_status' => self::STATUS_RUNNING,
                ':job_code' => $jobCode
            ]
        );
    }
}
