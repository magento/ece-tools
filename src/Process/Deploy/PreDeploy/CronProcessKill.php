<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Process\Deploy\PreDeploy;

use Magento\MagentoCloud\Process\ProcessInterface;
use Magento\MagentoCloud\Shell\ShellInterface;
use Psr\Log\LoggerInterface;

/**
 * Restoring writable directories.
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
     * Executes the process.
     *
     * @return void
     */
    public function execute()
    {
        $cronPids = $this->shell->execute("pgrep -f 'bin/magento cron:run'");
        if ($cronPids) {
            foreach ($cronPids as $pid) {
                $this->shell->execute("kill $pid");
                $this->logger->info(sprintf('Cron process with pid % was killed', $pid));
            }
        } else {
            $this->logger->info('Cron processes were not found to be killed');
        }
    }
}
