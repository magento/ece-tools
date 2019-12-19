<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Step\Deploy\InstallUpdate;

use Magento\MagentoCloud\Step\StepInterface;
use Psr\Log\LoggerInterface;

/**
 * Updates application configs.
 */
class ConfigUpdate implements StepInterface
{
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var StepInterface
     */
    private $steps;

    /**
     * @param LoggerInterface $logger
     * @param StepInterface $steps
     */
    public function __construct(
        LoggerInterface $logger,
        StepInterface $steps
    ) {
        $this->logger = $logger;
        $this->steps = $steps;
    }

    /**
     * @inheritdoc
     */
    public function execute()
    {
        $this->logger->info('Updating configuration from environment variables.');
        $this->steps->execute();
    }
}
