<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Step\Deploy;

use Magento\MagentoCloud\App\Error;
use Magento\MagentoCloud\Cron\Switcher;
use Magento\MagentoCloud\Filesystem\FileSystemException;
use Magento\MagentoCloud\Step\StepException;
use Magento\MagentoCloud\Step\StepInterface;
use Psr\Log\LoggerInterface;

/**
 * Set flag for disabling Magento cron jobs and kills all existed Magento cron processes
 */
class DisableCron implements StepInterface
{
    /**
     * @var BackgroundProcessKill
     */
    private $backgroundProcessKill;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var Switcher
     */
    private $cronSwitcher;

    /**
     * @param BackgroundProcessKill $backgroundProcessKill
     * @param Switcher $cronSwitcher
     * @param LoggerInterface $logger
     */
    public function __construct(
        BackgroundProcessKill $backgroundProcessKill,
        Switcher $cronSwitcher,
        LoggerInterface $logger
    ) {
        $this->backgroundProcessKill = $backgroundProcessKill;
        $this->cronSwitcher = $cronSwitcher;
        $this->logger = $logger;
    }

    /**
     * Process set Magento flag for disabling running cron jobs
     * and kill all existed Magento cron processes.
     *
     * {@inheritdoc}
     */
    public function execute()
    {
        try {
            $this->logger->info('Disable cron');

            $this->cronSwitcher->disable();
            $this->backgroundProcessKill->execute();
        } catch (FileSystemException $e) {
            throw new StepException($e->getMessage(), Error::DEPLOY_ENV_PHP_IS_NOT_WRITABLE);
        }
    }
}
