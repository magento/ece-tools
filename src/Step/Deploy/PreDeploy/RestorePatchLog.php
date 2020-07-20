<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Step\Deploy\PreDeploy;

use Magento\MagentoCloud\Filesystem\DirectoryList;
use Magento\MagentoCloud\Filesystem\Driver\File;
use Magento\MagentoCloud\Filesystem\FileList;
use Magento\MagentoCloud\Filesystem\FileSystemException;
use Magento\MagentoCloud\Package\UndefinedPackageException;
use Magento\MagentoCloud\Step\StepException;
use Magento\MagentoCloud\Step\StepInterface;
use Psr\Log\LoggerInterface;

/**
 * Restores patch log file.
 */
class RestorePatchLog implements StepInterface
{
    /**
     * @var DirectoryList
     */
    private $directoryList;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var File
     */
    private $file;

    /**
     * @var FileList
     */
    private $fileList;

    /**
     * @param LoggerInterface $logger
     * @param DirectoryList $directoryList
     * @param File $file
     * @param FileList $fileList
     */
    public function __construct(
        LoggerInterface $logger,
        DirectoryList $directoryList,
        File $file,
        FileList $fileList
    ) {
        $this->logger = $logger;
        $this->directoryList = $directoryList;
        $this->file = $file;
        $this->fileList = $fileList;
    }

    /**
     * Restores patch log file from build phase if needed.
     *
     * @return void
     * @throws StepException
     */
    public function execute()
    {
        try {
            $buildPhaseLogPath = $this->fileList->getInitPatchLog();
            if ($this->file->isExists($buildPhaseLogPath)) {
                $this->logger->info('Restoring patch log file');
                $this->file->createDirectory($this->directoryList->getLog());
                $buildPhaseLogContent = $this->file->fileGetContents($buildPhaseLogPath);
                $this->file->filePutContents(
                    $this->fileList->getPatchLog(),
                    $buildPhaseLogContent,
                    FILE_APPEND
                );
            }
        } catch (FileSystemException | UndefinedPackageException $exception) {
            throw new StepException($exception->getMessage(), $exception->getCode(), $exception);
        }
    }
}
