<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Filesystem\Flag;

use Magento\MagentoCloud\Filesystem\DirectoryList;
use Magento\MagentoCloud\Filesystem\Driver\File;
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
    public const FLAG_REGENERATE = 'regenerate';

    /**
     * Used to mark that static content deployment was performed on build phase.
     */
    public const FLAG_STATIC_CONTENT_DEPLOY_IN_BUILD = 'scd_in_build';

    /**
     * Used to mark that deploy hook is failed.
     */
    public const FLAG_DEPLOY_HOOK_IS_FAILED = 'deploy_is_failed';

    /**
     * Used to mark that splitting database should be ignored
     */
    public const FLAG_IGNORE_SPLIT_DB = 'ignore_split_db';

    /**
     * Used to mark that env.php file does not exist at the beginning of deployment
     */
    public const FLAG_ENV_FILE_ABSENCE = 'env_file_absence';

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
    private $pool;

    /**
     * @param LoggerInterface $logger
     * @param File $file
     * @param Pool $pool
     * @param DirectoryList $directoryList
     */
    public function __construct(
        LoggerInterface $logger,
        File $file,
        Pool $pool,
        DirectoryList $directoryList
    ) {
        $this->logger = $logger;
        $this->file = $file;
        $this->pool = $pool;
        $this->directoryList = $directoryList;
    }

    /**
     * Determines whether or not a flag exists.
     *
     * @param string $key
     * @return bool
     * @throws ConfigurationMismatchException If flag with given key is not registered
     */
    public function exists(string $key): bool
    {
        $path = $this->directoryList->getMagentoRoot() . '/' . $this->getFlagPath($key);

        return $this->file->isExists($path);
    }

    /**
     * Sets a flag on the file system.
     *
     * @param string $key
     * @return bool Returns false if file for required flag was not created, otherwise returns true
     * @throws ConfigurationMismatchException
     */
    public function set(string $key): bool
    {
        $flag = $this->getFlagPath($key);
        $path = $this->directoryList->getMagentoRoot() . '/' . $flag;

        if ($this->file->touch($path)) {
            $this->logger->info('Set flag: ' . $flag);

            return true;
        }

        $this->logger->notice(sprintf(
            'Cannot create flag %s',
            $key
        ));

        return false;
    }

    /**
     * Deletes a flag from the filesystem.
     *
     * @param string $key
     * @return bool Returns true if file does not exist or was removed by this method
     * @throws ConfigurationMismatchException
     */
    public function delete(string $key): bool
    {
        $flag = $this->getFlagPath($key);

        if (!$this->exists($key)) {
            $this->logger->debug(sprintf('Flag %s has already been deleted.', $flag));

            return true;
        }

        $path = $this->directoryList->getMagentoRoot() . '/' . $flag;

        if ($this->file->deleteFile($path)) {
            $this->logger->info('Deleting flag: ' . $flag);

            return true;
        }

        return false;
    }

    /**
     * Returns relative flag path by given flag key.
     *
     * @param string $key
     * @return string
     * @throws ConfigurationMismatchException If flag with given key is not registered
     */
    public function getFlagPath(string $key): string
    {
        $flag = $this->pool->get($key);

        if (!$flag) {
            throw new ConfigurationMismatchException(sprintf(
                'Flag with key %s is not registered in pool',
                $key
            ));
        }

        return $flag;
    }
}
