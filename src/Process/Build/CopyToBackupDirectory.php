<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Process\Build;

use Magento\MagentoCloud\Config\Environment;
use Magento\MagentoCloud\Filesystem\DirectoryList;
use Magento\MagentoCloud\Filesystem\Driver\File;
use Magento\MagentoCloud\Process\ProcessInterface;
use Magento\MagentoCloud\Util\BackgroundDirectoryCleaner;
use Psr\Log\LoggerInterface;

/**
 *
 * Writable directories will be erased when the writable filesystem is mounted to them. This
 * step backs them up to ./init/
 *
 * {@inheritdoc}
 */
class CopyToBackupDirectory implements ProcessInterface
{
    /**
     * @var File
     */
    private $file;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var Environment
     */
    private $environment;

    /**
     * @var DirectoryList
     */
    private $directoryList;

    /**
     * @var BackgroundDirectoryCleaner
     */
    private $cleaner;

    /**
     * @param File $file
     * @param LoggerInterface $logger
     * @param Environment $environment
     * @param DirectoryList $directoryList
     * @param BackgroundDirectoryCleaner $cleaner
     */
    public function __construct(
        File $file,
        LoggerInterface $logger,
        Environment $environment,
        DirectoryList $directoryList,
        BackgroundDirectoryCleaner $cleaner
    ) {
        $this->file = $file;
        $this->logger = $logger;
        $this->environment = $environment;
        $this->directoryList = $directoryList;
        $this->cleaner = $cleaner;
    }

    /**
     * @inheritdoc
     */
    public function execute()
    {
        $magentoRoot = $this->directoryList->getMagentoRoot();
        $backupDir = $this->directoryList->getPath('backup');

        $this->environment->clearFlag(Environment::REGENERATE_FLAG);

        $this->logger->info("Copying restorable directories to backup directory.");

        foreach (array_values($this->environment->getRestorableDirectories()) as $dir) {
            $srcDir = "$magentoRoot/$dir";
            $dstDir = "$backupDir/$dir";
            $this->logger->info("Reinitialize $srcDir");
            $this->file->createDirectory($srcDir, Environment::DEFAULT_DIRECTORY_MODE);
            $this->logger->info("Reinitialize $dstDir");
            $this->file->createDirectory($dstDir, Environment::DEFAULT_DIRECTORY_MODE);

            if (count($this->file->scanDir($srcDir)) > 2) {
                $this->file->copyDirectory($srcDir, $dstDir);
                $this->logger->info("Copied $srcDir to $dstDir");
                $this->cleaner->backgroundClearDirectory($srcDir);
            }
        }
        if ($this->environment->hasFlag(Environment::STATIC_CONTENT_DEPLOY_FLAG)) {
            $this->environment->setFlag($backupDir . '/' . Environment::STATIC_CONTENT_DEPLOY_FLAG);
        }
    }
}
