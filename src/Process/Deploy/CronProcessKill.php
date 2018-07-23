<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Process\Deploy;

use Magento\MagentoCloud\Process\ProcessInterface;
use Magento\MagentoCloud\Shell\ShellInterface;
use Psr\Log\LoggerInterface;

/**
 * Kills all running Magento cron processes
 */
class CronProcessKill implements ProcessInterface
{
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var ShellInterface
     */
    private $shell;

    /**
     * @param LoggerInterface $logger
     * @param ShellInterface $shell
     */
    public function __construct(
        LoggerInterface $logger,
        ShellInterface $shell
    ) {
        $this->logger = $logger;
        $this->shell = $shell;
    }

    /**
     * Kills all running Magento cron jobs.
     *
     * @return void
     */
    public function execute()
    {
        try {
            $cronPids = $this->shell->execute("pgrep -f 'bin/magento cron:run'");
            foreach ($cronPids as $pid) {
                $this->shell->execute("kill $pid");
                $this->logger->info(sprintf('Cron process with pid % was killed.', $pid));
            }
        } catch (\RuntimeException $e) {
            // pgrep returns 1 when no processes matched. Returns 2 and 3 in case of error
            if ($e->getCode() == 1) {
                $this->logger->info('Running Magento cron processes were not found.');
            } else {
                throw $e;
            }
        }
    }
}
