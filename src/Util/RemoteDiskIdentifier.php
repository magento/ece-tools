<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Util;

use Magento\MagentoCloud\Filesystem\Driver\File;
use Magento\MagentoCloud\Filesystem\DirectoryList;
use Magento\MagentoCloud\Shell\ShellInterface;

/**
 * Provides remote disk tools.
 */
class RemoteDiskIdentifier
{
    const REMOTE_DISK_RE = '/^([1-9]|\/dev\/rbd[0-9]+)/';

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
     * @param ShellInterface $shell
     * @param File $file
     * @param DirectoryList $directoryList
     */
    public function __construct(
        ShellInterface $shell,
        File $file,
        DirectoryList $directoryList
    ) {
        $this->shell = $shell;
        $this->file = $file;
        $this->directoryList = $directoryList;
    }

    /**
     * Return absolute path
     *
     * @param string $path
     * @return string
     */
    private function normalizePath(string $path): string
    {
        $magentoRoot = $this->directoryList->getMagentoRoot();

        // Fix up relative paths in relation to magento root
        if (strpos($path, $magentoRoot) !== 0) {
            $path = $magentoRoot . '/' . $path;
        }

        return $this->file->getRealPath($path);
    }

    /**
     * Return the first element from structured df command output
     *
     * @param array $output
     * @return string|null
     */
    private function getDiskFromOutput(array $output)
    {
        if (count($output) === 2) {
            $cols = preg_split('/\s+/', $output[1]);

            return array_shift($cols);
        }
    }

    /**
     * Use df to determine if a provided path is on remote storage
     *
     * @param string $path
     * @return bool
     */
    public function isOnRemoteDisk(string $path): bool
    {
        $path = $this->normalizePath($path);

        if ($this->file->isDirectory($path)) {
            $output = $this->shell->execute('df ' . escapeshellarg($path));
            $disk = $this->getDiskFromOutput($output);

            if ($disk) {
                return preg_match(self::REMOTE_DISK_RE, $disk);
            }
        }

        return false;
    }

    /**
     * Negated usage of isOnRemoteDisk
     *
     * @param string $path
     * @return bool
     */
    public function isOnLocalDisk(string $path): bool
    {
        return !$this->isOnRemoteDisk($path);
    }
}
