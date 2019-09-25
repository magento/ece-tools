<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Process\Deploy;

use Magento\MagentoCloud\Process\ProcessInterface;
use Psr\Log\LoggerInterface;

/**
 * @inheritdoc
 */
class PreDeploy implements ProcessInterface
{
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var ProcessInterface[]
     */
    private $processes;

    /**
     * @param LoggerInterface $logger
     * @param ProcessInterface[] $processes
     */
    public function __construct(
        LoggerInterface $logger,
        array $processes
    ) {
        $this->logger = $logger;
        $this->processes = $processes;
    }

    /**
     * Runs all processes that have to be run before deploy starting.
     * Enabling maintenance mode afterward.
     *
     * It's impossible to enable maintenance mode before pre-deploy processes as bin/magento command
     * can't be run without some files that are copying during files restoring from build phase.
     *
     * {@inheritdoc}
     */
    public function execute()
    {
        $this->logger->notice('Starting pre-deploy.');

        foreach ($this->processes as $process) {
            $process->execute();
        }

        $this->logger->notice('End of pre-deploy.');
    }
}
