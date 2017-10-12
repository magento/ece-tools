<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Process\Build;

use Magento\MagentoCloud\Process\ProcessInterface;
use Magento\MagentoCloud\Filesystem\DirectoryList;
use Magento\MagentoCloud\Filesystem\Driver\File;
use Psr\Log\LoggerInterface;

/**
 * @inheritdoc
 */
class ClearBackupDirectory implements ProcessInterface
{
    /**
     * @var File
     */
    private $file;

    /**
     * @var DirectoryList
     */
    private $directoryList;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param File $file
     * @param DirectoryList $directoryList
     * @param LoggerInterface $logger
     */
    public function __construct(
        File $file,
        DirectoryList $directoryList,
        LoggerInterface $logger
    ) {
        $this->file = $file;
        $this->directoryList = $directoryList;
        $this->logger = $logger;
    }

    /**
     * @inheritdoc
     */
    public function execute()
    {
        $backupDir = $this->directoryList->getPath('backup');
        $envPhpPath = $this->directoryList->getMagentoRoot() . '/app/etc/env.php';

        if ($this->file->isExists($backupDir)) {
            $this->logger->info("Clearing backup directory: $backupDir");
            $this->file->clearDirectory($backupDir);
        }

        if ($this->file->isExists($envPhpPath)) {
            $this->logger->info("Deleting env.php");
            $this->file->deleteFile($envPhpPath);
        }
    }
}
