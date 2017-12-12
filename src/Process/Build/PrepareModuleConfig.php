<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Process\Build;

use Magento\MagentoCloud\Shell\ShellInterface;
use Magento\MagentoCloud\Process\ProcessInterface;
use Magento\MagentoCloud\Config\Shared as SharedConfig;
use Magento\MagentoCloud\Util\ModuleInformation;
use Psr\Log\LoggerInterface;

/**
 * @inheritdoc
 */
class PrepareModuleConfig implements ProcessInterface
{
    /**
     * @var SharedConfig
     */
    private $sharedConfig;

    /**
     * @var ShellInterface
     */
    private $shell;

    /**
     * @var moduleInformation
     */
    private $moduleInformation;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param SharedConfig $sharedConfig
     * @param ShellInterface $shell
     * @param moduleInformation $moduleInformation
     * @param LoggerInterface $logger
     */
    public function __construct(
        SharedConfig $sharedConfig,
        ShellInterface $shell,
        moduleInformation $moduleInformation,
        LoggerInterface $logger
    ) {
        $this->sharedConfig = $sharedConfig;
        $this->shell = $shell;
        $this->moduleInformation = $moduleInformation;
        $this->logger = $logger;
    }

    /**
     * @inheritdoc
     */
    public function execute()
    {
        $this->logger->info('Reconciling installed modules with shared config.');
        $moduleConfig = $this->sharedConfig->get('modules');

        if (empty($moduleConfig)) {
            $this->logger->info('Shared config file is missing module section. Updating with all installed modules.');
            $this->shell->execute('php bin/magento module:enable --all');
            return;
        }

        /*
         NOTE: This way has problems as described in MAGECLOUD-1424
         Note: I'm leaving this in for now, but we should change soon.
        */
        $newModules = $this->moduleInformation->getNewModuleNames();

        if (empty($newModules)) {
            $this->logger->info('All installed modules present in shared config.');
            return;
        }

        $this->logger->info('Enabling newly installed modules not found in shared config.');
        $enableModules = join(" ", $newModules);
        $this->shell->execute("php bin/magento module:enable $enableModules");
    }
}
