<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\EceToolExtend\Step\Deploy;

use Magento\MagentoCloud\Step\StepInterface;
use Psr\Log\LoggerInterface;

class PreDeploy implements StepInterface
{
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param LoggerInterface $logger
     */
    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * Executes the step.
     *
     * @return void
     */
    public function execute()
    {
        $this->logger->info('Running demo pre deploy step');
    }
}
