<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Command;

use Magento\MagentoCloud\Util\MaintenanceModeSwitcher;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Magento\MagentoCloud\Process\ProcessInterface;
use Magento\MagentoCloud\Filesystem\Flag\Manager as FlagManager;
use Magento\MagentoCloud\Package\Manager as PackageManager;

/**
 * CLI command for deploy hook. Responsible for installing/updating/configuring Magento
 */
class Deploy extends Command
{
    const NAME = 'deploy';

    /**
     * @var ProcessInterface
     */
    private $process;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var FlagManager
     */
    private $flagManager;

    /**
     * @var PackageManager
     */
    private $packageManager;

    /**
     * @var MaintenanceModeSwitcher
     */
    private $maintenanceModeSwitcher;

    /**
     * @param ProcessInterface $process
     * @param LoggerInterface $logger
     * @param FlagManager $flagManager
     * @param PackageManager $packageManager
     * @param MaintenanceModeSwitcher $maintenanceModeSwitcher
     */
    public function __construct(
        ProcessInterface $process,
        LoggerInterface $logger,
        FlagManager $flagManager,
        PackageManager $packageManager,
        MaintenanceModeSwitcher $maintenanceModeSwitcher
    ) {
        $this->process = $process;
        $this->logger = $logger;
        $this->flagManager = $flagManager;
        $this->packageManager = $packageManager;
        $this->maintenanceModeSwitcher = $maintenanceModeSwitcher;

        parent::__construct();
    }

    /**
     * @inheritdoc
     */
    protected function configure()
    {
        $this->setName(static::NAME)
            ->setDescription('Deploys application');

        parent::configure();
    }

    /**
     * Deploy application: copy writable directories back, install or update Magento data.
     *
     * {@inheritdoc}
     *
     * @throws \Throwable
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        try {
            $this->flagManager->delete(FlagManager::FLAG_DEPLOY_HOOK_IS_FAILED);
            $this->logger->notice('Starting deploy. ' . $this->packageManager->getPrettyInfo());

            $this->process->execute();

            $this->maintenanceModeSwitcher->disable();
            $this->logger->notice('Deployment completed.');
        } catch (\Throwable $e) {
            $this->flagManager->set(FlagManager::FLAG_DEPLOY_HOOK_IS_FAILED);
            $this->logger->critical($e->getMessage());

            throw $e;
        }
    }
}
