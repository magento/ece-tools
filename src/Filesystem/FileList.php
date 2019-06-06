<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Filesystem;

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
     * @param DirectoryList $directoryList
     * @param SystemList $systemList
     */
    public function __construct(DirectoryList $directoryList, SystemList $systemList)
    {
        $this->directoryList = $directoryList;

        parent::__construct($systemList);
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
    public function getTtfbLog(): string
    {
        return $this->directoryList->getLog() . '/ttfb_results.json';
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

    /**
     * @return string
     */
    public function getMagentoDockerCompose(): string
    {
        return $this->directoryList->getMagentoRoot() . '/docker-compose.yml';
    }

    /**
     * @return string
     */
    public function getToolsDockerCompose(): string
    {
        return $this->directoryList->getRoot() . '/docker-compose.yml';
    }

    /**
     * @return string
     */
    public function getAppConfig(): string
    {
        return $this->directoryList->getMagentoRoot() . '/.magento.app.yaml';
    }

    /**
     * @return string
     */
    public function getServicesConfig(): string
    {
        return $this->directoryList->getMagentoRoot() . '/.magento/services.yaml';
    }
}
