<?php
/**
 * Origin filesystem driver
 *
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Filesystem\Driver;

use Magento\MagentoCloud\Filesystem\FileSystemException;
use Magento\MagentoCloud\Shell\ShellInterface;

/**
 * Class File.
 *
 * @package Magento\Framework\Filesystem\Driver
 */
class File
{
    /**
     * @var ShellInterface
     */
    private $shell;

    /**
     * @param ShellInterface $shell
     */
    public function __construct(ShellInterface $shell)
    {
        $this->shell = $shell;
    }

    /**
     * Returns last warning message string
     *
     * @return string
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
     * @throws FileSystemException
     */
    public function isExists($path): bool
    {
        clearstatcache();
        $result = @file_exists($path);
        if ($result === null) {
            $this->fileSystemException('Error occurred during execution %1', [$this->getWarningMessage()]);
        }

        return $result;
    }

    /**
     * Parse a configuration file.
     *
     * @param string $path
     * @param bool $processSections
     * @param int $scannerMode
     * @return array|bool
     */
    public function parseIni($path, $processSections = false, $scannerMode = INI_SCANNER_NORMAL)
    {
        clearstatcache();
        $result = @parse_ini_file($path, $processSections, $scannerMode);
        if (false === $result) {
            $this->fileSystemException('Cannot read contents from file "%1" %2', [$path, $this->getWarningMessage()]);
        }

        return $result;
    }

    /**
     * Create directory
     *
     * @param string $path
     * @param int $permissions
     * @return bool
     * @throws FileSystemException
     */
    public function createDirectory($path, $permissions = 0777)
    {
        return $this->mkdirRecursive($path, $permissions);
    }

    /**
     * Create a directory recursively taking into account race conditions
     *
     * @param string $path
     * @param int $permissions
     * @return bool
     * @throws FileSystemException
     */
    private function mkdirRecursive($path, $permissions = 0777)
    {
        if (is_dir($path)) {
            return true;
        }
        $parentDir = dirname($path);
        while (!is_dir($parentDir)) {
            $this->mkdirRecursive($parentDir, $permissions);
        }
        $result = @mkdir($path, $permissions);
        if (!$result) {
            if (is_dir($path)) {
                $result = true;
            } else {
                $this->fileSystemException('Directory "%1" cannot be created %2', [$path, $this->getWarningMessage()]);
            }
        }

        return $result;
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
            throw new FileSystemException($e->getMessage(), $e);
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
     * Copy source into destination
     *
     * @param string $source
     * @param string $destination
     * @return bool
     * @throws FileSystemException
     */
    public function copy($source, $destination)
    {
        $result = @copy($source, $destination);
        if (!$result) {
            $this->fileSystemException(
                'The file or directory "%1" cannot be copied to "%2" %3',
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
        $this->shell->execute(sprintf(
            '/bin/bash -c "shopt -s dotglob; cp -R %s/* %s/"',
            $source,
            $destination
        ));
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
     * @throws FileSystemException
     */
    public function deleteFile($path)
    {
        $result = @unlink($path);
        if (!$result) {
            $this->fileSystemException('The file "%1" cannot be deleted %2', [$path, $this->getWarningMessage()]);
        }

        return $result;
    }

    /**
     * Recursive delete directory
     *
     * @param string $path
     * @return bool
     * @throws FileSystemException
     */
    public function deleteDirectory($path)
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
        $result = @rmdir($path);
        if (!$result) {
            $this->fileSystemException(
                'The directory "%1" cannot be deleted %2',
                [$path, $this->getWarningMessage()]
            );
        }

        return $result;
    }

    /**
     * Recursive clear directory
     *
     * @param string $path
     * @return bool
     * @throws FileSystemException
     */
    public function clearDirectory($path)
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
     * Sets access and modification time of file.
     *
     * @param string $path
     * @param int|null $modificationTime
     * @return bool
     * @throws FileSystemException
     */
    public function touch($path, $modificationTime = null)
    {
        if (!$modificationTime) {
            $result = @touch($path);
        } else {
            $result = @touch($path, $modificationTime);
        }
        if (!$result) {
            $this->fileSystemException(
                'The file or directory "%1" cannot be touched %2',
                [$path, $this->getWarningMessage()]
            );
        }

        return $result;
    }

    /**
     * Write contents to file in given path
     *
     * @param string $path
     * @param string $content
     * @param string|null $mode
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
     * @param string $path
     * @return mixed
     */
    public function requireFile(string $path)
    {
        return require $path;
    }
}
