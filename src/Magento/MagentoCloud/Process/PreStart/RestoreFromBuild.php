<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Process\PreStart;

use Magento\MagentoCloud\Process\ProcessInterface;
use Magento\MagentoCloud\Shell\ShellInterface;
use Magento\MagentoCloud\Filesystem\Driver\File;
use Magento\MagentoCloud\Config\Environment;
use Magento\MagentoCloud\Filesystem\DirectoryList;
use Magento\MagentoCloud\Util\BackgroundDirectoryCleaner;
use Psr\Log\LoggerInterface;

/**
 * @inheritdoc
 */
class RestoreFromBuild implements ProcessInterface
{
    /**
     * @var Environment
     */
    private $environment;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var ShellInterface
     */
    private $shell;

    /**
     * @var File
     */
    private $file;

    /**
     * @var DirectoryList
     */
    private $directoryList;

    /**
     * @var BackgroundDirectoryCleaner
     */
    private $cleaner;

    /**
     * @param Environment $environment
     * @param LoggerInterface $logger
     * @param ShellInterface $shell
     * @param File $file
     * @param DirectoryList $directoryList
     * @param BackgroundDirectoryCleaner $cleaner
     */
    public function __construct(
        Environment $environment,
        LoggerInterface $logger,
        ShellInterface $shell,
        File $file,
        DirectoryList $directoryList,
        BackgroundDirectoryCleaner $cleaner
    ) {
        $this->environment = $environment;
        $this->logger = $logger;
        $this->shell = $shell;
        $this->file = $file;
        $this->directoryList = $directoryList;
        $this->cleaner = $cleaner;
    }

    /**
     * @inheritdoc
     */
    public function execute()
    {
        if ($this->environment->hasFlag(Environment::DEPLOY_READY_FLAG)) {
            $this->logger->info("Environment is ready for deployment. Aborting pre-start.");
            return;
        }

        $magentoRoot = $this->directoryList->getMagentoRoot();
        $paths = $this->environment->getRestorableDirectories();
        $backupDir = $this->directoryList->getPath('backup');
        $cloud_flags = "$magentoRoot/" . $paths['cloud_flags'];

        if (!$this->file->isDirectory($cloud_flags)) {
            $this->file->createDirectory($cloud_flags, Environment::DEFAULT_DIRECTORY_MODE);
        }

        $this->logger->info('Restoring recoverable data from backup.');
        $this->environment->setFlag(Environment::PRE_START_FLAG);

        foreach (array_values($paths) as $path) {
            $top = "$magentoRoot/$path";
            $backup = "$backupDir/$path";

            if ($path === $paths['static']) {
                // Copy backup to local storage and symlink top level to it
                $localDir = $this->directoryList->getPath('local');
                if ($this->file->isWritable($localDir)) {
                    $local = "$localDir/$path";
                    $this->cleaner->backgroundDeleteDirectory($local);
                    $this->cleaner->backgroundClearDirectory($top);
                    $this->file->copyDirectory($backup, $local);
                    $this->environment->symlinkDirectoryContents($local, $top);
                    continue;
                }

                // Symlink top level to backup dir when environment override is not present
                if (!$this->environment->isVariableDisabled('STATIC_CONTENT_SYMLINK')) {
                    $this->cleaner->backgroundClearDirectory($top);
                    $this->environment->symlinkDirectoryContents($backup, $top);
                    continue;
                }
            }
            $this->file->copyDirectory($backup, $top);
            $this->logger->info("Copied $backup to $top");
        }

        $this->environment->clearFlag(Environment::PRE_START_FLAG);
        $this->environment->setFlag(Environment::DEPLOY_READY_FLAG);
    }
}
