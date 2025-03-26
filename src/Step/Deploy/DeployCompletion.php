<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Step\Deploy;

use Magento\MagentoCloud\Config\Application\HookChecker;
use Magento\MagentoCloud\Step\StepInterface;
use Psr\Log\LoggerInterface;

/**
 * Checks if post_deploy hook is configured, if not runs processes otherwise do nothing.
 */
class DeployCompletion implements StepInterface
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
     * @var StepInterface[]
     */
    private $steps;

    /**
     * @param LoggerInterface $logger
     * @param HookChecker $hookChecker
     * @param StepInterface[] $steps
     */
    public function __construct(
        LoggerInterface $logger,
        HookChecker $hookChecker,
        array $steps
    ) {
        $this->logger = $logger;
        $this->hookChecker = $hookChecker;
        $this->steps = $steps;
    }

    /**
     * @inheritdoc
     */
    public function execute()
    {
        if ($this->hookChecker->isPostDeployHookEnabled()) {
            $this->logger->info(
                'Post-deploy hook enabled. Cron enabling, cache flushing and pre-warming operations ' .
                'are postponed to post-deploy stage.'
            );

            return;
        }

        foreach ($this->steps as $step) {
            $step->execute();
        }
    }
}
