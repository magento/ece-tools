<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Process\Deploy;

use Magento\MagentoCloud\Cron\JobUnlocker;
use Magento\MagentoCloud\Process\ProcessInterface;
use Psr\Log\LoggerInterface;

/**
 * Updates running cron jobs.
 *
 * In magento version 2.2 was implemented locking functionality for cron jobs, new cron jobs can't be started
 * if exist job in status 'running' with same 'job_code'.
 * This process are used for unlocking cron jobs that stuck in 'running' status.
 */
class UnlockCronJobs implements ProcessInterface
{
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var JobUnlocker
     */
    private $jobUnlocker;

    /**
     * @param JobUnlocker $jobUnlocker
     * @param LoggerInterface $logger
     */
    public function __construct(
        JobUnlocker $jobUnlocker,
        LoggerInterface $logger
    ) {
        $this->jobUnlocker = $jobUnlocker;
        $this->logger = $logger;
    }

    /**
     * Updates running cron jobs to status 'missed'.
     *
     * {@inheritdoc}
     */
    public function execute()
    {
        $updatedJobsCount = $this->jobUnlocker->unlockAll();

        if ($updatedJobsCount) {
            $this->logger->info(
                sprintf(
                    '%d cron jobs were updated from status "%s" to status "%s"',
                    $updatedJobsCount,
                    JobUnlocker::STATUS_RUNNING,
                    JobUnlocker::STATUS_ERROR
                )
            );
        }
    }
}
