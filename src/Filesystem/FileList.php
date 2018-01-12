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
class FileList
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
    public function __construct(DirectoryList $directoryList, File $file)
    {
        $this->directoryList = $directoryList;
        $this->file = $file;
    }

    /**
     * @return string
     */
    public function getConfig(): string
    {
        return $this->directoryList->getMagentoRoot() . '/app/etc/config.php';
    }

    /**
     * @return string
     */
    public function getEnv(): string
    {
        return $this->directoryList->getMagentoRoot() . '/app/etc/env.php';
    }

    /**
     * @return string
     */
    public function getBuildConfig(): string
    {
        return $this->directoryList->getMagentoRoot() . '/build_options.ini';
    }

    /**
     * @return string
     * @throws FileSystemException
     */
    public function getComposer(): string
    {
        $magentoComposer = $this->directoryList->getMagentoRoot() . '/composer.json';

        if ($this->file->isExists($magentoComposer)) {
            return $magentoComposer;
        }

        /**
         * Workaround for local development.
         */
        return $this->directoryList->getRoot() . '/composer.json';
    }

    /**
     * @return string
     */
    public function getEnvConfig(): string
    {
        return $this->directoryList->getMagentoRoot() . '/.magento.env.yaml';
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
}
