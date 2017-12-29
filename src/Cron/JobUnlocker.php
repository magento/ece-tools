<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Cron;

use Magento\MagentoCloud\DB\ConnectionInterface;

/**
 * Unlocks cron jobs stacked in status 'running'.
 */
class JobUnlocker
{
    const STATUS_RUNNING = 'running';
    const STATUS_ERROR = 'error';

    const UPGRADE_UNLOCK_MESSAGE = 'The job is terminated due to system upgrade';

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
     * @param string $message
     * @return int Count of updated rows.
     */
    public function unlockAll(string $message = self::UPGRADE_UNLOCK_MESSAGE): int
    {
        $updateCronStatusQuery = 'UPDATE `cron_schedule` SET `status` = :to_status, `messages` = :messages'
            . ' WHERE `status` = :from_status';

        return $this->connection->affectingQuery(
            $updateCronStatusQuery,
            [
                ':to_status' => self::STATUS_ERROR,
                ':from_status' => self::STATUS_RUNNING,
                ':messages' => $message
            ]
        );
    }

    /**
     * Moves cron jobs with given job_code from status running to status missed.
     *
     * @param string $jobCode Cron job code.
     * @param string $message
     * @return int Count of updated rows.
     */
    public function unlockByJobCode(string $jobCode, string $message = self::UPGRADE_UNLOCK_MESSAGE): int
    {
        $updateCronStatusQuery = 'UPDATE `cron_schedule` SET `status` = :to_status, `messages` = :messages'
            . ' WHERE `status` = :from_status AND `job_code` = :job_code';

        return $this->connection->affectingQuery(
            $updateCronStatusQuery,
            [
                ':to_status' => self::STATUS_ERROR,
                ':from_status' => self::STATUS_RUNNING,
                ':job_code' => $jobCode,
                ':messages' => $message
            ]
        );
    }
}
