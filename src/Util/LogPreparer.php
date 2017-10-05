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

class LogPreparer
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
     * @throws \RuntimeException
     * @return void
     */
    public function prepare()
    {
        $deployLogPath = $this->loggerConfig->getDeployLogPath();
        $buildLogPath = $this->loggerConfig->getBackupBuildLogPath();
        $buildLogContent = file_get_contents($buildLogPath);

        $buildLogIsApplied = $this->buildLogIsApplied($deployLogPath, $buildLogContent);

        if ($this->environment->hasEnvironmentChanged()) {
            $this->invalidateLogs();
            $this->environment->syncEnvironmentId();
        }

        if (file_exists($deployLogPath) && !$buildLogIsApplied) {
            $this->file->filePutContents($deployLogPath, $buildLogContent, FILE_APPEND);
        } elseif (!$buildLogIsApplied) {
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
        if (!file_exists($deployLogPath)) {
            return false;
        }
        return false !== strpos(file_get_contents($deployLogPath), $buildLogContent);
    }

    private function invalidateLogs()
    {
        $logDir = $this->directoryList->getMagentoRoot().'/var/log';
        array_map('unlink', glob($logDir . '/*.log'));
    }
}
