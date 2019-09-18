<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Process\Deploy;

use Magento\MagentoCloud\Config\Application\HookChecker;
use Magento\MagentoCloud\Process\ProcessInterface;
use Psr\Log\LoggerInterface;

/**
 * Checks if post_deploy hook is configured, if not runs processes otherwise do nothing.
 */
class DeployCompletion implements ProcessInterface
{
    /**
     * @var HookChecker
     */
    private $hookChecker;

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
     * @param HookChecker $hookChecker
     * @param ProcessInterface[] $processes
     */
    public function __construct(
        LoggerInterface $logger,
        HookChecker $hookChecker,
        array $processes
    ) {
        $this->logger = $logger;
        $this->hookChecker = $hookChecker;
        $this->processes = $processes;
    }

    /**
     * @inheritdoc
     */
    public function execute()
    {
        if ($this->hookChecker->isPostDeployHookEnabled()) {
            $this->logger->info(
                'Post-deploy hook enabled. Cron enabling, cache cleaning and pre-warming operations ' .
                'are postponed to post-deploy stage.'
            );

            return;
        }

        foreach ($this->processes as $process) {
            $process->execute();
        }
    }
}
