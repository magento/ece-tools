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
     * This flag is creating by magento for cleaning up generated/code, generated/metadata and var/cache directories
     * for subsequent regeneration of this content.
     */
    const FLAG_REGENERATE = 'regenerate';

    /**
     * Used to mark that static content deployment was performed on build phase.
     */
    const FLAG_STATIC_CONTENT_DEPLOY_IN_BUILD = 'scd_in_build';

    /**
     * Used to mark that deploy hook is failed.
     */
    const FLAG_DEPLOY_HOOK_IS_FAILED = 'deploy_is_failed';

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
        $path = $this->directoryList->getMagentoRoot() . '/' . $this->getFlagPath($flagKey);

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
     * @return bool Returns false if file for required flag was not created, otherwise returns true
     */
    public function set(string $flagKey): bool
    {
        $flagPath = $this->getFlagPath($flagKey);
        $path = $this->directoryList->getMagentoRoot() . '/' . $flagPath;

        if ($this->file->touch($path)) {
            $this->logger->info('Set flag: ' . $flagPath);

            return true;
        }

        $this->logger->notice(sprintf(
            'Cannot create flag %s',
            $flagKey
        ));

        return false;
    }

    /**
     * Deletes a flag from the filesystem.
     *
     * @param string $flagKey
     * @return bool Returns true if file does not exist or was removed by this method
     * @throws \RuntimeException If flag with given key is not registered
     */
    public function delete(string $flagKey): bool
    {
        $flagPath = $this->getFlagPath($flagKey);

        if (!$this->exists($flagKey)) {
            $this->logger->debug(sprintf('Flag %s has already been deleted.', $flagPath));
            return true;
        }

        $path = $this->directoryList->getMagentoRoot() . '/' . $flagPath;

        try {
            if ($this->file->deleteFile($path)) {
                $this->logger->info('Deleting flag: ' . $flagPath);
                return true;
            }
        } catch (FileSystemException $e) {
            $this->logger->notice($e->getMessage());
        }

        return false;
    }

    /**
     * Returns relative flag path by given flag key.
     *
     * @param string $flagKey
     * @return string
     * @throws \RuntimeException If flag with given key is not registered
     */
    public function getFlagPath(string $flagKey): string
    {
        $flagPath = $this->flagPool->get($flagKey);

        if (!$flagPath) {
            throw new \RuntimeException(sprintf('Flag with key %s is not registered in flagPool', $flagKey));
        }

        return $flagPath;
    }
}
