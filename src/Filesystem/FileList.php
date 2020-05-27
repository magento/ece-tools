<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Filesystem;

use Magento\MagentoCloud\Package\UndefinedPackageException;

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
     * @throws UndefinedPackageException
     */
    public function getCloudLog(): string
    {
        return $this->directoryList->getLog() . '/cloud.log';
    }

    /**
     * @return string
     * @throws UndefinedPackageException
     */
    public function getCloudErrorLog(): string
    {
        return $this->directoryList->getLog() . '/cloud.error.log';
    }

    /**
     * @return string
     * @throws UndefinedPackageException
     */
    public function getTtfbLog(): string
    {
        return $this->directoryList->getLog() . '/ttfb_results.json';
    }

    /**
     * @return string
     * @throws UndefinedPackageException
     */
    public function getInitCloudLog(): string
    {
        return $this->directoryList->getInit() . '/var/log/cloud.log';
    }

    /**
     * @return string
     * @throws UndefinedPackageException
     */
    public function getInitCloudErrorLog(): string
    {
        return sprintf(
            '%s/%s/cloud.error.log',
            $this->directoryList->getInit(),
            $this->directoryList->getPath(DirectoryList::DIR_LOG, true)
        );
    }

    /**
     * @return string
     * @throws UndefinedPackageException
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

    /**
     * Return the path to the service EOL configuration file.
     *
     * @return string
     */
    public function getServiceEolsConfig(): string
    {
        return $this->directoryList->getRoot() . '/config/eol.yaml';
    }

    /**
     * @return string
     */
    public function getEnvDistConfig(): string
    {
        return $this->directoryList->getMagentoRoot() . '/.magento.env.md';
    }

    /**
     * @return string
     */
    public function getLogDistConfig(): string
    {
        return $this->directoryList->getRoot() . '/dist/.log.env.md';
    }

    /**
     * @return string
     */
    public function getFrontStaticDist(): string
    {
        return $this->directoryList->getRoot() . '/dist/front-static.php.dist';
    }

    /**
     * Returns path to schema.error.yaml file
     *
     * @return string
     */
    public function getErrorSchema(): string
    {
        return $this->directoryList->getRoot() . '/config/schema.error.yaml';
    }
}
