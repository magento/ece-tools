<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Step\Deploy;

use Magento\MagentoCloud\Step\StepInterface;
use Psr\Log\LoggerInterface;

/**
 * @inheritdoc
 */
class PreDeploy implements StepInterface
{
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
     * @param StepInterface[] $steps
     */
    public function __construct(
        LoggerInterface $logger,
        array $steps
    ) {
        $this->logger = $logger;
        $this->steps = $steps;
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

        foreach ($this->steps as $step) {
            $step->execute();
        }

        $this->logger->notice('End of pre-deploy.');
    }
}
