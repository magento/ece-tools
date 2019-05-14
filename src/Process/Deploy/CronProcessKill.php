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
            $this->logger->info('Trying to kill running cron jobs');

            $process = $this->shell->execute('pgrep -U "$(id -u)" -f "bin/magento cron:run"');

            $cronPids = array_filter(explode(PHP_EOL, $process->getOutput()));
            foreach ($cronPids as $pid) {
                $this->killProcess($pid);
            }
        } catch (\RuntimeException $e) {
            // pgrep returns 1 when no processes matched. Returns 2 and 3 in case of error
            if ($e->getCode() === 1) {
                $this->logger->info('Running Magento cron processes were not found.');
            } else {
                $this->logger->warning('Error happening during kill cron: ' . $e->getMessage());
            }
        }
    }

    /**
     * Runs command for killing the process by PID.
     * Sometimes there may be an error when process was already finished before killing it.
     *
     * @param string $pid
     * @return void
     */
    private function killProcess($pid)
    {
        try {
            $this->shell->execute("kill $pid");
        } catch (\RuntimeException $e) {
            $this->logger->info('There is an error during killing the cron processes: ' . $e->getMessage());
        }
    }
}
