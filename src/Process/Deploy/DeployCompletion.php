<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
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
     * @var ProcessInterface
     */
    private $process;

    /**
     * @param LoggerInterface $logger
     * @param HookChecker $hookChecker
     * @param ProcessInterface $process
     */
    public function __construct(
        LoggerInterface $logger,
        HookChecker $hookChecker,
        ProcessInterface $process
    ) {
        $this->logger = $logger;
        $this->hookChecker = $hookChecker;
        $this->process = $process;
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

        $this->process->execute();
    }
}
