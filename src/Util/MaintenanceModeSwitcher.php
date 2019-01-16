<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Util;

use Magento\MagentoCloud\Config\Stage\DeployInterface;
use Magento\MagentoCloud\Filesystem\DirectoryList;
use Magento\MagentoCloud\Filesystem\Driver\File;
use Magento\MagentoCloud\Package\UndefinedPackageException;
use Magento\MagentoCloud\Shell\ShellException;
use Magento\MagentoCloud\Shell\ShellInterface;
use Psr\Log\LoggerInterface;

/**
 * Uses for enabling and disabling magento maintenance mode on deploy phase
 */
class MaintenanceModeSwitcher
{
    /**
     * Maintenance flag file name
     */
    const FLAG_FILENAME = '.maintenance.flag';

    /**
     * @var ShellInterface
     */
    private $shell;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var DeployInterface
     */
    private $stageConfig;

    /**
     * @var File
     */
    private $file;

    /**
     * @var DirectoryList
     */
    private $directoryList;

    /**
     * @param ShellInterface $shell
     * @param LoggerInterface $logger
     * @param DeployInterface $stageConfig
     * @param File $file
     * @param DirectoryList $directoryList
     */
    public function __construct(
        ShellInterface $shell,
        LoggerInterface $logger,
        DeployInterface $stageConfig,
        File $file,
        DirectoryList $directoryList
    ) {
        $this->shell = $shell;
        $this->logger = $logger;
        $this->stageConfig = $stageConfig;
        $this->file = $file;
        $this->directoryList = $directoryList;
    }

    /**
     * Enables maintenance mode
     *
     * @return void
     * @throws \RuntimeException
     */
    public function enable()
    {
        $this->logger->notice('Enabling Maintenance mode');
        try {
            $this->shell->execute(sprintf(
                'php ./bin/magento maintenance:enable --ansi --no-interaction %s',
                $this->stageConfig->get(DeployInterface::VAR_VERBOSE_COMMANDS)
            ));
        } catch (ShellException $e) {
            $this->logger->warning(
                'Command maintenance:enable finished with an error. Creating maintenance flag flag manually.'
            );
            $this->file->touch($this->getMaintenanceFlagPath());
        }
    }

    /**
     * Disable maintenance mode
     *
     * @return void
     * @throws \RuntimeException
     */
    public function disable()
    {
        try {
            $this->shell->execute(sprintf(
                'php ./bin/magento maintenance:disable --ansi --no-interaction %s',
                $this->stageConfig->get(DeployInterface::VAR_VERBOSE_COMMANDS)
            ));
        } catch (ShellException $e) {
            $this->logger->warning(
                'Command maintenance:disable finished with an error. Deleting maintenance flag flag manually.'
            );
            $this->file->deleteFile($this->getMaintenanceFlagPath());
        }
        $this->logger->notice('Maintenance mode is disabled.');
    }

    /**
     * Returns path to maintenance flag file
     *
     * @return string
     * @throws \RuntimeException if DirectoryList class can't get magento package version
     */
    private function getMaintenanceFlagPath()
    {
        try {
            return $this->directoryList->getVar() . '/' . self::FLAG_FILENAME;
        } catch (UndefinedPackageException $e) {
            throw new \RuntimeException($e->getMessage(), $e->getCode(), $e);
        }
    }
}
