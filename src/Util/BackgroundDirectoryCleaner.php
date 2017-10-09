<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Util;

use Magento\MagentoCloud\Filesystem\Driver\File;
use Magento\MagentoCloud\Filesystem\DirectoryList;
use Magento\MagentoCloud\Shell\ShellInterface;
use Psr\Log\LoggerInterface;

class BackgroundDirectoryCleaner
{
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
     * @param LoggerInterface $logger
     * @param ShellInterface $shell
     * @param File $file
     */
    public function __construct(
        LoggerInterface $logger,
        ShellInterface $shell,
        File $file
    ) {
        $this->logger = $logger;
        $this->shell = $shell;
        $this->file = $file;
    }

    /**
     *  Handle deleting directories in the background
     *
     * @param string $path
     * @return void
     */
    public function backgroundDeleteDirectory(string $path)
    {
        $timestamp = time();
        $tempDir = "$path.$timestamp";

        if (!$this->file->isExists($path)) {
            return;
        }

        if (is_link($path)) {
            $this->file->deleteFile($path);
            return;
        }

        if (rename($path, $tempDir)) {
            $this->logger->info("Moving $path to $tempDir for background removal");
            $this->shell->backgroundExecute("rm -rf $tempDir");
            return;
        }
    }

    /**
     * Handle deleting contents of a directory in the background
     *
     * @param string $path Path to flush
     * @return void
     */
    public function backgroundClearDirectory(string $path)
    {
        $timestamp = time();
        $tempDir = $path . '/' . preg_replace('/\//', '_', $path) . "_$timestamp";

        if (!file_exists($tempDir)) {
            mkdir($tempDir, \Magento\MagentoCloud\Config\Environment::DEFAULT_DIRECTORY_MODE, true);
        }

        foreach (new \DirectoryIterator($path) as $fileInfo) {
            $fileName = $fileInfo->getFilename();
            if ($fileInfo->isDot() || $fileName == basename($tempDir)) {
                continue;
            }
            if (rename("$path/$fileName", "$tempDir/$fileName")) {
                $this->logger->info("Moved $path/$fileName to $tempDir for background removal");
            }
        }
        $this->logger->info("Contents removed. Deleting $tempDir in the background");
        $this->shell->backgroundExecute("rm -rf $tempDir");
    }
}
