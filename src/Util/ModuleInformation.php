<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Util;

use Magento\MagentoCloud\Config\Shared;
use Magento\MagentoCloud\Filesystem\DirectoryList;
use Magento\MagentoCloud\Filesystem\Driver\File;
use Magento\MagentoCloud\Package\Manager;

class ModuleInformation
{
    /**
     * @var DirectoryList
     */
    private $directoryList;

    /**
     * @var File
     */
    private $file;

    /**
     * @var Shared
     */
    private $sharedConfig;

    /**
     * @var Manager
     */
    private $manager;

    /**
     * @param DirectoryList $directoryList
     * @param File $file
     * @param Shared $sharedConfig
     * @param Manager $manager
     */
    public function __construct(
        DirectoryList $directoryList,
        File $file,
        Shared $sharedConfig,
        Manager $manager
    ) {
        $this->directoryList = $directoryList;
        $this->file = $file;
        $this->sharedConfig = $sharedConfig;
        $this->manager = $manager;
    }

    /**
     * Return module name from module.xml file
     *
     * @param string $package Package name (path) to module directory relative to vendor
     * @return string
     */
    public function getModuleNameByPackage(string $package): string
    {
        $name = '';
        $moduleXML = $this->directoryList->getMagentoRoot() . '/vendor/' . $package . '/etc/module.xml';
        if ($this->file->isExists($moduleXML)) {
            $xml = simplexml_load_file($moduleXML);
            $name = (string)$xml->module->attributes()->name ?? '';
        }
        return $name;
    }

    /**
     * Parse package names from composer requirements and return a list of third party module names
     *
     * @param string[] $packages Array of package names to lookup
     * @return string[] Third party module names
     */
    public function getThirdPartyModuleNames(array $packages): array
    {
        $modules = [];
        foreach ($packages as $package) {
            if (strpos($package, 'magento/', 0) === 0) {
                continue;
            }
            $name = $this->getModuleNameByPackage($package);
            if (!empty($name)) {
                $modules[] = $name;
            }
        }
        return $modules;
    }

    /**
     * Retrieve a list of module names installed but not present in shared config
     *
     * @return string[] Module names
     */
    public function getNewModuleNames(): array
    {
        $moduleConfig = $this->sharedConfig->get('modules');
        $requiredPackages = $this->manager->getRequiredPackageNames();
        $thirdPartyModules = $this->getThirdPartyModuleNames($requiredPackages);
        $newModules = array_filter(array_diff($thirdPartyModules, array_keys($moduleConfig)));
        return $newModules;
    }
}
