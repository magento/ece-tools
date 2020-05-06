<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\App\Logger\Prepare;

use Magento\MagentoCloud\App\LoggerException;
use Magento\MagentoCloud\Filesystem\DirectoryList;
use Magento\MagentoCloud\Filesystem\Driver\File;
use Magento\MagentoCloud\Filesystem\FileList;
use Magento\MagentoCloud\Filesystem\FileSystemException;
use Magento\MagentoCloud\Package\UndefinedPackageException;

/**
 * Uses for copying error log file from build phase if needed.
 */
class ErrorLogFile
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
     * @var FileList
     */
    private $fileList;

    /**
     * @param File $file
     * @param DirectoryList $directoryList
     * @param FileList $fileList
     *
     */
    public function __construct(
        File $file,
        DirectoryList $directoryList,
        FileList $fileList
    ) {
        $this->file = $file;
        $this->directoryList = $directoryList;
        $this->fileList = $fileList;
    }

    /**
     * Checks and copies error log file from build phase if needed
     *
     * @throws LoggerException
     */
    public function prepare()
    {
        try {
            $this->file->createDirectory($this->directoryList->getLog());

            $deployErrorLogPath = $this->fileList->getCloudErrorLog();
            $buildPhaseErrorLogPath = $this->fileList->getInitCloudErrorLog();
            if ($this->isNeedToCopyBuildErrorLogFile($deployErrorLogPath, $buildPhaseErrorLogPath)) {
                $this->file->copy($buildPhaseErrorLogPath, $deployErrorLogPath);
            }
        } catch (FileSystemException | UndefinedPackageException $exception) {
            throw new LoggerException(
                $exception->getMessage(),
                $exception->getCode(),
                $exception
            );
        }
    }

    /**
     * Checks if error log file from build phase should be copied
     *
     * @param string $deployLogPath
     * @param string $buildPhaseLogPath
     * @return bool
     * @throws FileSystemException
     */
    private function isNeedToCopyBuildErrorLogFile(string $deployLogPath, string $buildPhaseLogPath): bool
    {
        $buildLogFileExists = $this->file->isExists($buildPhaseLogPath);
        if (!$buildLogFileExists) {
            return false;
        }

        $deployLogFileExists = $this->file->isExists($deployLogPath);
        if (!$deployLogFileExists) {
            return true;
        }

        return false === strpos(
            $this->file->fileGetContents($deployLogPath),
            $this->file->fileGetContents($buildPhaseLogPath)
        );
    }
}
