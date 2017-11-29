<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Filesystem;

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
     * @param DirectoryList $directoryList
     */
    public function __construct(DirectoryList $directoryList)
    {
        $this->directoryList = $directoryList;
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
     */
    public function getComposer(): string
    {
        return $this->directoryList->getMagentoRoot() . '/composer.json';
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
        return $this->directoryList->getLog() . '/installUpgrade.log';
    }
}
