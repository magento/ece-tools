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
     * {@inheritdoc}
     */
    public function execute()
    {
        $this->logger->info("Trying to kill running cron jobs");
        $this->shell->execute("pkill -f 'bin/magento cron:run'");
    }
}
