<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Process\Build;

use Magento\MagentoCloud\Filesystem\FileList;
use Magento\MagentoCloud\Process\ProcessInterface;
use Magento\MagentoCloud\Filesystem\DirectoryList;
use Magento\MagentoCloud\Filesystem\Driver\File;
use Psr\Log\LoggerInterface;

/**
 * @inheritdoc
 */
class ClearInitDirectory implements ProcessInterface
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
     * @var FileList
     */
    private $fileList;

    /**
     * @param File $file
     * @param DirectoryList $directoryList
     * @param FileList $fileList
     * @param LoggerInterface $logger
     */
    public function __construct(
        File $file,
        DirectoryList $directoryList,
        FileList $fileList,
        LoggerInterface $logger
    ) {
        $this->file = $file;
        $this->directoryList = $directoryList;
        $this->fileList = $fileList;
        $this->logger = $logger;
    }

    /**
     * @inheritdoc
     */
    public function execute()
    {
        $envPhpPath = $this->fileList->getEnv();
        $initPath = $this->directoryList->getInit();
        $this->logger->info('Clearing temporary directory.');

        if ($this->file->isExists($initPath)) {
            $this->file->clearDirectory($initPath);
        }
        // app/etc/env.php appears after running bin/magento on Build phase, so we need to remove it
        if ($this->file->isExists($envPhpPath)) {
            $this->file->deleteFile($envPhpPath);
        }
    }
}
