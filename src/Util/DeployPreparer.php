<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Util;

use Magento\MagentoCloud\Config\Environment;
use Magento\MagentoCloud\Config\Logger as LoggerConfig;
use Magento\MagentoCloud\Filesystem\DirectoryList;
use Magento\MagentoCloud\Filesystem\Driver\File;

class DeployPreparer
{
    /**
     * @var LoggerConfig
     */
    private $loggerConfig;

    /**
     * @var File
     */
    private $file;

    /**
     * @var DirectoryList
     */
    private $directoryList;

    /**
     * @var Environment
     */
    private $environment;

    /**
     * LogPreparer constructor.
     * @param LoggerConfig $loggerConfig
     * @param File $file
     * @param DirectoryList $directoryList
     * @param Environment $environment
     */
    public function __construct(
        LoggerConfig $loggerConfig,
        File $file,
        DirectoryList $directoryList,
        Environment $environment
    ) {
        $this->loggerConfig = $loggerConfig;
        $this->file = $file;
        $this->directoryList = $directoryList;
        $this->environment = $environment;
    }

    /**
     * Prepares the deploy log for further use.
     *
     * @return void
     */
    public function prepare()
    {
        $deployLogPath = $this->loggerConfig->getDeployLogPath();
        $buildLogPath = $this->loggerConfig->getBackupBuildLogPath();
        $buildLogContent = $this->file->fileGetContents($buildLogPath);

        if ($this->file->isExists($deployLogPath) && !$this->buildLogIsApplied($deployLogPath, $buildLogContent)) {
            $this->file->filePutContents($deployLogPath, $buildLogContent, FILE_APPEND);
        } else {
            $this->file->createDirectory(dirname($deployLogPath));
            $this->file->copy($buildLogPath, $deployLogPath);
        }
    }

    /**
     * Checks if the log contains the content of the build phase.
     *
     * @param string $deployLogPath deploy log path
     * @param string $buildLogContent build log content
     * @return bool
     */
    private function buildLogIsApplied(string $deployLogPath, string $buildLogContent): bool
    {
        return false !== strpos($this->file->fileGetContents($deployLogPath), $buildLogContent);
    }
}
