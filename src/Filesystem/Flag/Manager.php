<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Filesystem\Flag;

use Magento\MagentoCloud\Filesystem\DirectoryList;
use Magento\MagentoCloud\Filesystem\Driver\File;
use Magento\MagentoCloud\Filesystem\FileSystemException;
use Psr\Log\LoggerInterface;

/**
 * Performs operations with file flags.
 */
class Manager
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
     * @var Pool
     */
    private $flagPool;

    /**
     * @param LoggerInterface $logger
     * @param File $file
     * @param Pool $flagPool
     * @param DirectoryList $directoryList
     */
    public function __construct(
        LoggerInterface $logger,
        File $file,
        Pool $flagPool,
        DirectoryList $directoryList
    ) {
        $this->logger = $logger;
        $this->file = $file;
        $this->flagPool = $flagPool;
        $this->directoryList = $directoryList;
    }

    /**
     * Determines whether or not a flag exists.
     *
     * @param string $flagKey
     * @return bool
     * @throws \RuntimeException If flag with given key is not registered
     */
    public function exists(string $flagKey): bool
    {
        $flag = $this->getFlag($flagKey);
        $path = $this->directoryList->getMagentoRoot() . '/' . $flag->getPath();

        try {
            return $this->file->isExists($path);
        } catch (FileSystemException $e) {
            $this->logger->notice($e->getMessage());
        }

        return false;
    }

    /**
     * Sets a flag on the file system.
     *
     * @param string $flagKey
     * @return bool
     * @throws \RuntimeException If flag with given key is not registered
     */
    public function set(string $flagKey): bool
    {
        $flag = $this->getFlag($flagKey);
        $flagPath = $flag->getPath();
        $path = $this->directoryList->getMagentoRoot() . '/' . $flagPath;

        try {
            if ($this->file->touch($path)) {
                $this->logger->info('Set flag: ' . $flagPath);
                return true;
            }
        } catch (FileSystemException $e) {
            $this->logger->notice($e->getMessage());
        }

        return false;
    }

    /**
     * Deletes a flag from the filesystem.
     *
     * @param string $flagKey
     * @return bool
     * @throws \RuntimeException If flag with given key is not registered
     */
    public function delete(string $flagKey): bool
    {
        $flag = $this->getFlag($flagKey);
        $flagBasePath = $flag->getPath();

        if (!$this->exists($flagKey)) {
            $this->logger->info(sprintf('Flag %s is already deleted.', $flagBasePath));
            return true;
        }

        $path = $this->directoryList->getMagentoRoot() . '/' . $flagBasePath;

        try {
            if ($this->file->deleteFile($path)) {
                $this->logger->info('Deleting flag: ' . $flagBasePath);
                return true;
            }
        } catch (FileSystemException $e) {
            $this->logger->notice($e->getMessage());
        }

        return false;
    }

    /**
     * Returns flag object by given flag key.
     *
     * @param string $flagKey
     * @return FlagInterface
     * @throws \RuntimeException If flag with given key is not registered
     */
    public function getFlag(string $flagKey): FlagInterface
    {
        $flag = $this->flagPool->get($flagKey);

        if (!$flag instanceof FlagInterface) {
            throw new \RuntimeException(sprintf('Flag with key %s is not registered in flagPool', $flagKey));
        }

        return $flag;
    }
}
