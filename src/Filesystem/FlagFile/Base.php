<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Filesystem\FlagFile;

use Magento\MagentoCloud\Filesystem\DirectoryList;
use Magento\MagentoCloud\Filesystem\Driver\File;
use Magento\MagentoCloud\Filesystem\FileSystemException;
use Psr\Log\LoggerInterface;

/**
 * @inheritdoc
 */
class Base
{
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var File
     */
    private $file;

    /**
     * @var DirectoryList
     */
    private $directoryList;

    /**
     * @param LoggerInterface $logger
     * @param File $file
     * @param DirectoryList $directoryList
     */
    public function __construct(
        LoggerInterface $logger,
        File $file,
        DirectoryList $directoryList
    ) {
        $this->logger = $logger;
        $this->file = $file;
        $this->directoryList = $directoryList;
    }

    /**
     * @param string $flag
     * @return bool
     */
    public function exists(string $flag): bool
    {
        $path = $this->directoryList->getMagentoRoot() . '/' . $flag;
        try {
            return $this->file->isExists($path);
        } catch (FileSystemException $e) {
            $this->logger->notice($e->getMessage());
        }
        return false;
    }

    /**
     * @param string $flag
     * @return bool
     */
    public function set(string $flag): bool
    {
        $path = $this->directoryList->getMagentoRoot() . '/' . $flag;
        try {
            if ($this->file->touch($path)) {
                $this->logger->info('Set flag: ' . $flag);
                return true;
            }
        } catch (FileSystemException $e) {
            $this->logger->notice($e->getMessage());
        }
        return false;
    }

    /**
     * @param string $flag
     * @return bool
     */
    public function delete(string $flag): bool
    {
        if (!$this->exists($flag)) {
            $this->logger->info('Flag already deleted: ' . $flag);
            return true;
        }

        $path = $this->directoryList->getMagentoRoot() . '/' . $flag;

        try {
            if ($this->file->deleteFile($path)) {
                $this->logger->info('Deleted flag: ' . $flag);
                return true;
            }
        } catch (FileSystemException $e) {
            $this->logger->notice($e->getMessage());
        }

        return false;
    }
}
