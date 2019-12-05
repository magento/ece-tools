<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Step\PostDeploy;

use Magento\MagentoCloud\Cron\Switcher;
use Magento\MagentoCloud\Step\StepInterface;
use Psr\Log\LoggerInterface;

/**
 * Enables running Magento cron
 */
class EnableCron implements StepInterface
{
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var Switcher
     */
    private $cronSwitcher;

    /**
     * @param LoggerInterface $logger
     * @param Switcher $cronSwitcher
     */
    public function __construct(
        LoggerInterface $logger,
        Switcher $cronSwitcher
    ) {
        $this->logger = $logger;
        $this->cronSwitcher = $cronSwitcher;
    }

    /**
     * Enables Magento cron
     *
     * {@inheritdoc}
     */
    public function execute()
    {
        $this->logger->info('Enable cron');
        $this->cronSwitcher->enable();
    }
}
