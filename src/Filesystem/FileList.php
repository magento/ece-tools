<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Filesystem;

use Magento\MagentoCloud\Filesystem\Driver\File;

/**
 * Resolver of file configurations.
 */
class FileList extends ConfigFileList
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
     * @param SystemList $systemList
     * @param File $file
     */
    public function __construct(DirectoryList $directoryList, SystemList $systemList, File $file)
    {
        $this->directoryList = $directoryList;
        $this->file = $file;

        parent::__construct($systemList, $file);
    }

    /**
     * @return string
     */
    public function getCloudLog(): string
    {
        return $this->directoryList->getLog() . '/cloud.log';
    }

    /**
     * @return string
     */
    public function getInitCloudLog(): string
    {
        return $this->directoryList->getInit() . '/var/log/cloud.log';
    }

    /**
     * @return string
     */
    public function getInstallUpgradeLog(): string
    {
        return $this->directoryList->getLog() . '/install_upgrade.log';
    }

    /**
     * @return string
     */
    public function getPatches(): string
    {
        return $this->directoryList->getRoot() . '/patches.json';
    }

    /**
     * @return string
     */
    public function getMagentoComposer(): string
    {
        return $this->directoryList->getMagentoRoot() . '/composer.json';
    }
}
