<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Process\Deploy;

use Magento\MagentoCloud\Process\ProcessException;
use Magento\MagentoCloud\Process\ProcessInterface;
use Magento\MagentoCloud\Util\MaintenanceModeSwitcher;
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
     * @var ProcessInterface
     */
    private $process;

    /**
     * @var MaintenanceModeSwitcher
     */
    private $maintenanceModeSwitcher;

    /**
     * @param LoggerInterface $logger
     * @param ProcessInterface $process
     * @param MaintenanceModeSwitcher $maintenanceModeSwitcher
     */
    public function __construct(
        LoggerInterface $logger,
        ProcessInterface $process,
        MaintenanceModeSwitcher $maintenanceModeSwitcher
    ) {
        $this->logger = $logger;
        $this->process = $process;
        $this->maintenanceModeSwitcher = $maintenanceModeSwitcher;
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
        $this->process->execute();

        try {
            $this->maintenanceModeSwitcher->enable();
        } catch (\RuntimeException $exception) {
            throw new ProcessException($exception->getMessage(), $exception->getCode(), $exception);
        }
        $this->logger->notice('End of pre-deploy.');
    }
}
