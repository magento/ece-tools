<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Step\Deploy;

use Magento\MagentoCloud\Cron\JobUnlocker;
use Magento\MagentoCloud\Package\MagentoVersion;
use Magento\MagentoCloud\Package\UndefinedPackageException;
use Magento\MagentoCloud\Step\StepException;
use Magento\MagentoCloud\Step\StepInterface;
use Psr\Log\LoggerInterface;

/**
 * Updates running cron jobs.
 *
 * In magento version 2.2 was implemented locking functionality for cron jobs, new cron jobs can't be started
 * if exist job in status 'running' with same 'job_code'.
 * This process are used for unlocking cron jobs that stuck in 'running' status.
 */
class UnlockCronJobs implements StepInterface
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
     * @var MagentoVersion
     */
    private $magentoVersion;

    /**
     * @param JobUnlocker $jobUnlocker
     * @param LoggerInterface $logger
     * @param MagentoVersion $version
     */
    public function __construct(
        JobUnlocker $jobUnlocker,
        LoggerInterface $logger,
        MagentoVersion $version
    ) {
        $this->jobUnlocker = $jobUnlocker;
        $this->logger = $logger;
        $this->magentoVersion = $version;
    }

    /**
     * Updates running cron jobs to status 'error'.
     *
     * {@inheritdoc}
     */
    public function execute()
    {
        try {
            /**
             * Since version 2.2.2 Magento unlocks cron jobs during upgrade
             */
            if ($this->magentoVersion->isGreaterOrEqual('2.2.2')) {
                return;
            }
        } catch (UndefinedPackageException $exception) {
            throw new StepException($exception->getMessage(), $exception->getCode(), $exception);
        }

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
