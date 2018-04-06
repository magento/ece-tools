<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\App;

use Magento\MagentoCloud\Filesystem\DirectoryList;
use Magento\MagentoCloud\Filesystem\FileList;
use Magento\MagentoCloud\Filesystem\Driver\File;
use Magento\MagentoCloud\App\Logger\Pool;
use Magento\MagentoCloud\App\Logger\Processor\SanitizeProcessor;

/**
 * @inheritdoc
 */
class Logger extends \Monolog\Logger
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
     * @param Pool $pool
     * @param SanitizeProcessor $sanitizeProcessor
     */
    public function __construct(
        File $file,
        DirectoryList $directoryList,
        FileList $fileList,
        Pool $pool,
        SanitizeProcessor $sanitizeProcessor
    ) {
        $this->file = $file;
        $this->directoryList = $directoryList;
        $this->fileList = $fileList;

        $this->prepare();

        parent::__construct('default', $pool->getHandlers(), [$sanitizeProcessor]);
    }

    /**
     * Prepares the deploy log for further use.
     *
     * @return void
     */
    private function prepare()
    {
        $deployLogPath = $this->fileList->getCloudLog();
        $buildPhaseLogPath = $this->fileList->getInitCloudLog();
        $deployLogFileExists = $this->file->isExists($deployLogPath);
        $buildLogFileExists = $this->file->isExists($buildPhaseLogPath);
        $buildPhaseLogContent = $buildLogFileExists ? $this->file->fileGetContents($buildPhaseLogPath) : '';

        $this->file->createDirectory($this->directoryList->getLog());

        if ($deployLogFileExists
            && $buildPhaseLogContent
            && !$this->isBuildLogApplied($deployLogPath, $buildPhaseLogContent)
        ) {
            $this->file->filePutContents($deployLogPath, $buildPhaseLogContent, FILE_APPEND);
        } elseif (!$deployLogFileExists && $buildLogFileExists) {
            $this->file->copy($buildPhaseLogPath, $deployLogPath);
        }
    }

    /**
     * Checks if the log contains the content of the build phase.
     *
     * @param string $deployLogPath deploy log path
     * @param string $buildPhaseLogContent build log content
     * @return bool
     */
    private function isBuildLogApplied(string $deployLogPath, string $buildPhaseLogContent): bool
    {
        return false !== strpos($this->file->fileGetContents($deployLogPath), $buildPhaseLogContent);
    }
}
