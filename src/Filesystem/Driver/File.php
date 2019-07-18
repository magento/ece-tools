<?php
/**
 * Origin filesystem driver
 *
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Filesystem\Driver;

use Magento\MagentoCloud\Filesystem\FileSystemException;

/**
 * Class File.
 *
 * @package Magento\Framework\Filesystem\Driver
 */
class File
{
    /**
     * This is the prefix we use for directories we are deleting in the background.
     */
    const DELETING_PREFIX = 'DELETING_';

    /**
     * Returns last warning message string
     *
     * @return string|null
     */
    private function getWarningMessage()
    {
        $warning = error_get_last();
        if ($warning && $warning['type'] == E_WARNING) {
            return 'Warning!' . $warning['message'];
        }

        return null;
    }

    /**
     * Is file or directory exist in file system
     *
     * @param string $path
     * @return bool
     */
    public function isExists(string $path): bool
    {
        return @file_exists($path);
    }

    /**
     * Tells whether the filename is a link
     *
     * @param string $path
     * @return bool
     * @throws FileSystemException
     */
    public function isLink($path): bool
    {
        clearstatcache();
        $result = @is_link($path);
        if ($result === null) {
            $this->fileSystemException('Error occurred during execution %1', [$this->getWarningMessage()]);
        }

        return $result;
    }

    /**
     * @param string $path
     * @return bool
     */
    public function isDirectory(string $path): bool
    {
        return @is_dir($path);
    }

    /**
     * Unlink symlink path
     * Tells whether the filename is a link
     *
     * @param string $path
     * @return bool
     * @throws FileSystemException
     */
    public function unLink($path): bool
    {
        clearstatcache();
        $result = @unlink($path);
        if ($result === null) {
            $this->fileSystemException('Error occurred during execution %1', [$this->getWarningMessage()]);
        }

        return $result;
    }

    /**
     * Parse a configuration file.
     *
     * @param string $path
     * @return array|bool
     * @throws FileSystemException
     */
    public function parseIni($path)
    {
        clearstatcache();
        $result = @parse_ini_file($path);
        if (false === $result) {
            $this->fileSystemException('Cannot read contents from file "%1" %2', [$path, $this->getWarningMessage()]);
        }

        return $result;
    }

    /**
     * @param string $path
     * @param int $mode
     * @param bool $recursive
     * @return bool
     */
    public function createDirectory($path, $mode = 0755, $recursive = true): bool
    {
        return @mkdir($path, $mode, $recursive);
    }

    /**
     * Read directory
     *
     * @param string $path
     * @return string[]
     * @throws FileSystemException
     */
    public function readDirectory($path)
    {
        try {
            $flags = \FilesystemIterator::SKIP_DOTS | \FilesystemIterator::UNIX_PATHS;
            $iterator = new \FilesystemIterator($path, $flags);
            $result = [];
            /** @var \FilesystemIterator $file */
            foreach ($iterator as $file) {
                $result[] = $file->getPathname();
            }
            sort($result);

            return $result;
        } catch (\Exception $e) {
            throw new FileSystemException($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * Renames a file or directory
     *
     * @param string $oldPath
     * @param string $newPath
     * @return bool
     * @throws FileSystemException
     */
    public function rename($oldPath, $newPath)
    {
        $result = @rename($oldPath, $newPath);
        if (!$result) {
            $this->fileSystemException(
                'The path "%1" cannot be renamed into "%2" %3',
                [$oldPath, $newPath, $this->getWarningMessage()]
            );
        }

        return $result;
    }

    /**
     * Copy source into destination.
     *
     * @param string $source
     * @param string $destination
     * @return bool
     */
    public function copy(string $source, string $destination): bool
    {
        return copy($source, $destination);
    }

    /**
     * Copy directory recursively.
     *
     * @param string $source The path of source folder
     * @param string $destination The path of destination folder
     */
    public function copyDirectory($source, $destination)
    {
        /**
         * Use shell for best performance.
         */
        shell_exec(sprintf(
            '/bin/bash -c %s',
            escapeshellarg(sprintf(
                'shopt -s dotglob; cp -R %s/* %s/',
                escapeshellarg(rtrim($source, '/')),
                escapeshellarg(rtrim($destination, '/'))
            ))
        ));
    }

    /**
     * Test for an empty directory
     *
     * @param string $path
     * @return bool
     */
    public function isEmptyDirectory(string $path): bool
    {
        if ($this->isDirectory($path)) {
            $dirs = scandir($path, SCANDIR_SORT_NONE);

            if ($dirs && count($dirs) > 2) {
                return false;
            }
        }

        return true;
    }

    /**
     * Create symlink on source and place it into destination
     *
     * @param string $source
     * @param string $destination
     * @return bool
     * @throws FileSystemException
     */
    public function symlink($source, $destination)
    {
        $result = @symlink($source, $destination);
        if (!$result) {
            $this->fileSystemException(
                'Cannot create a symlink for "%1" and place it to "%2" %3',
                [
                    $source,
                    $destination,
                    $this->getWarningMessage(),
                ]
            );
        }

        return $result;
    }

    /**
     * Delete file
     *
     * @param string $path
     * @return bool
     */
    public function deleteFile(string $path): bool
    {
        return @unlink($path);
    }

    /**
     * Recursive delete directory
     *
     * @param string $path
     * @return bool
     * @codeCoverageIgnore
     */
    public function deleteDirectory(string $path): bool
    {
        $flags = \FilesystemIterator::SKIP_DOTS | \FilesystemIterator::UNIX_PATHS;
        $iterator = new \FilesystemIterator($path, $flags);
        /** @var \FilesystemIterator $entity */
        foreach ($iterator as $entity) {
            if ($entity->isDir()) {
                $this->deleteDirectory($entity->getPathname());
            } else {
                $this->deleteFile($entity->getPathname());
            }
        }

        return @rmdir($path);
    }

    /**
     * Recursive clear directory.
     *
     * @param string $path
     * @return bool
     * @codeCoverageIgnore
     */
    public function clearDirectory(string $path): bool
    {
        if (!$this->isExists($path)) {
            return true;
        }

        $flags = \FilesystemIterator::SKIP_DOTS | \FilesystemIterator::UNIX_PATHS;
        $iterator = new \FilesystemIterator($path, $flags);
        /** @var \FilesystemIterator $entity */
        foreach ($iterator as $entity) {
            if ($entity->isDir()) {
                $this->deleteDirectory($entity->getPathname());
            } else {
                $this->deleteFile($entity->getPathname());
            }
        }

        return true;
    }

    /**
     * Handle deleting contents of a directory in the background
     *
     * @param string $path Path to flush
     * @param array $excludes
     * @return void
     * @codeCoverageIgnore
     */
    public function backgroundClearDirectory(string $path, array $excludes = [])
    {
        if ($this->isLink($path)) {
            $this->deleteFile($path);
            return;
        }

        $timestamp = time();
        $tempDir = sprintf(
            '%s/%s%s_%s',
            $path,
            static::DELETING_PREFIX,
            preg_replace('/\//', '_', basename($path)),
            $timestamp
        );
        $excludes[] = $tempDir;

        if (!$this->isDirectory($tempDir)) {
            $this->createDirectory($tempDir);
        }

        foreach (new \DirectoryIterator($path) as $fileInfo) {
            $fileName = $fileInfo->getFilename();
            $src = "$path/$fileName";
            $dst = "$tempDir/$fileName";

            if ($fileInfo->isDot() || in_array($src, $excludes)) {
                continue;
            }

            if ($this->isLink($src)) {
                $this->deleteFile($src);
                continue;
            }

            if ($this->isExists($dst)) {
                ($this->isDirectory($dst)) ? $this->deleteDirectory($dst) : $this->deleteFile($dst);
            }
            $this->rename($src, $dst);
        }
        shell_exec('nohup rm -rf ' . escapeshellarg($tempDir) . ' 1>/dev/null 2>&1 &');
    }

    /**
     * Sets access and modification time of file.
     *
     * @param string $path
     * @param int|null $time
     * @return bool
     */
    public function touch($path, $time = null): bool
    {
        return @touch($path, $time);
    }

    /**
     * Write contents to file in given path
     *
     * @param string $path
     * @param string $content
     * @param int|null $mode
     * @return int The number of bytes that were written.
     * @throws FileSystemException
     */
    public function filePutContents($path, $content, $mode = null)
    {
        $result = @file_put_contents($path, $content, $mode);
        if (!$result) {
            $this->fileSystemException(
                'The specified "%1" file could not be written %2',
                [$path, $this->getWarningMessage()]
            );
        }

        return $result;
    }

    /**
     * Throw a FileSystemException with a message and optional arguments
     *
     * @param string $message
     * @param array $arguments
     * @return void
     * @throws FileSystemException
     */
    private function fileSystemException($message, $arguments = [])
    {
        if ($arguments) {
            $placeholders = array_map(
                function ($key) {
                    return '%' . (is_int($key) ? strval($key + 1) : $key);
                },
                array_keys($arguments)
            );
            $pairs = array_combine($placeholders, $arguments);
            $message = strtr($message, $pairs);
        }

        throw new FileSystemException($message);
    }

    /**
     * Get real path
     *
     * @param string $path
     *
     * @return string|bool
     */
    public function getRealPath($path)
    {
        return realpath($path);
    }

    public function scanDir(string $path)
    {
        clearstatcache();
        $result = @scandir($path);
        if (false === $result) {
            $this->fileSystemException('Cannot read contents from path "%1" %2', [$path, $this->getWarningMessage()]);
        }

        return $result;
    }

    /**
     * Retrieve file contents from given path
     *
     * @param string $path
     * @param bool $useIncludedPath
     * @param resource|null $context
     * @return string
     * @throws FileSystemException
     */
    public function fileGetContents($path, $useIncludedPath = false, $context = null)
    {
        clearstatcache();
        $result = @file_get_contents($path, $useIncludedPath, $context);
        if (false === $result) {
            $this->fileSystemException('Cannot read contents from file "%1" %2', [$path, $this->getWarningMessage()]);
        }

        return $result;
    }

    /**
     * Returns directory iterator for given path
     *
     * @param string $path
     * @return \DirectoryIterator
     */
    public function getDirectoryIterator($path): \DirectoryIterator
    {
        return new \DirectoryIterator($path);
    }

    /**
     * @param string $path
     * @return mixed
     */
    public function requireFile(string $path)
    {
        return require $path;
    }
}
