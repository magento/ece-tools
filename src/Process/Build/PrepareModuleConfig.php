<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Process\Build;

use Magento\MagentoCloud\Shell\ShellInterface;
use Magento\MagentoCloud\Process\ProcessInterface;
use Magento\MagentoCloud\Config\Shared as SharedConfig;
use Magento\MagentoCloud\Package\Manager;
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
     * @var Manager
     */
    private $manager;

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
     * @param Manager $manager
     * @param moduleInformation $moduleInformation
     * @param LoggerInterface $logger
     */
    public function __construct(
        SharedConfig $sharedConfig,
        ShellInterface $shell,
        Manager $manager,
        moduleInformation $moduleInformation,
        LoggerInterface $logger
    ) {
        $this->sharedConfig = $sharedConfig;
        $this->shell = $shell;
        $this->manager = $manager;
        $this->moduleInformation = $moduleInformation;
        $this->logger = $logger;
    }

    /**
     * Parse package names from composer requirements and return a list of third party module names
     *
     * @param string[] $packages Array of package names to lookup
     * @return string[] Third party module names
     */
    private function getThirdPartyModuleNames(array $packages): array
    {
        $modules = [];
        foreach ($packages as $package) {
            if (strpos($package, 'magento/', 0) === 0) {
                continue;
            }
            $modules[] = $this->moduleInformation->getModuleNameByPackage($package);
        }
        return $modules;
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

        $requiredPackages = $this->manager->getRequiredPackageNames();
        $thirdPartyModules = $this->getThirdPartyModuleNames($requiredPackages);
        $newModules = array_filter(array_diff($thirdPartyModules, array_keys($moduleConfig)));

        if (empty($newModules)) {
            $this->logger->info('All installed modules present in shared config.');
            return;
        }


        $this->logger->info('Enabling newly installed modules not found in shared config.');
        $enableModules = join(" ", $newModules);
        $this->shell->execute("php bin/magento module:enable $enableModules");
    }
}
