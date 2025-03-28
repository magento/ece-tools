<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Step\Build;

use Magento\MagentoCloud\Step\StepInterface;
use Psr\Log\LoggerInterface;

/**
 * Copies the data to the ./init/ directory
 *
 * {@inheritdoc}
 */
class BackupData implements StepInterface
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
    public function __construct(LoggerInterface $logger, array $steps)
    {
        $this->logger = $logger;
        $this->steps = $steps;
    }

    /**
     * @inheritdoc
     */
    public function execute()
    {
        $this->logger->notice('Copying data to the ./init directory');

        foreach ($this->steps as $step) {
            $step->execute();
        }

        $this->logger->notice('End of copying data to the ./init directory');
    }
}
