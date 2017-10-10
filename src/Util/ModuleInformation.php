<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Util;

use Magento\MagentoCloud\Filesystem\DirectoryList;
use Magento\MagentoCloud\Filesystem\Driver\File;

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
     * @param DirectoryList $directoryList
     * @param File $file
     */
    public function __construct(
        DirectoryList $directoryList,
        File $file
    ) {
        $this->directoryList = $directoryList;
        $this->file = $file;
    }

    /**
     * Return module name from module.xml file
     *
     * @param string $package Package name (path) to module directory relative to vendor
     */
    public function getModuleNameByPackage(string $package)
    {
        $moduleXML = $this->directoryList->getMagentoRoot() . '/vendor/' . $package . '/etc/module.xml';
        if ($this->file->isExists($moduleXML)) {
            $xml = simplexml_load_file($moduleXML);
            return (string)$xml->module->attributes()->name ?? '';
        }
    }
}
