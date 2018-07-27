<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Process\Build\BackupData;

use Magento\MagentoCloud\Config\GlobalSection as GlobalConfig;
use Magento\MagentoCloud\Filesystem\DirectoryList;
use Magento\MagentoCloud\Filesystem\Driver\File;
use Magento\MagentoCloud\Process\ProcessInterface;
use Psr\Log\LoggerInterface;
use Monolog\Logger;
use Magento\MagentoCloud\App\Logger\Pool as LoggerPool;

/**
 * Writable directories will be erased when the writable filesystem is mounted to them.
 * This step backs them up to ./init/
 *
 * {@inheritdoc}
 */
class WritableDirectories implements ProcessInterface
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
     * @var GlobalConfig
     */
    private $globalConfig;

    /**
     * @var LoggerInterface|Logger
     */
    private $logger;

    /**
     * @var LoggerPool
     */
    private $loggerPool;

    /**
     * @param File $file
     * @param DirectoryList $directoryList
     * @param GlobalConfig $globalConfig
     * @param LoggerInterface $logger
     * @param LoggerPool $loggerPool
     */
    public function __construct(
        File $file,
        DirectoryList $directoryList,
        GlobalConfig $globalConfig,
        LoggerInterface $logger,
        LoggerPool $loggerPool
    ) {
        $this->file = $file;
        $this->directoryList = $directoryList;
        $this->globalConfig = $globalConfig;
        $this->logger = $logger;
        $this->loggerPool = $loggerPool;
    }

    public function execute()
    {
        $magentoRoot = $this->directoryList->getMagentoRoot() . '/';
        $rootInitDir = $this->directoryList->getPath(DirectoryList::DIR_INIT) . '/';
        $viewPreprocessedDir = $this->directoryList->getPath(DirectoryList::DIR_VIEW_PREPROCESSED, true);
        $logDir = $this->directoryList->getPath(DirectoryList::DIR_LOG, true);
        $writableDirectories = $this->directoryList->getWritableDirectories();

        $this->logger->info(sprintf('Copying writable directories to %s directory.', $rootInitDir));

        foreach ($writableDirectories as $dir) {
            if ($dir == $logDir) {
                continue;
            }

            $originalDir = $magentoRoot . $dir;

            if (!$this->file->isExists($originalDir)) {
                $this->logger->notice(sprintf('Directory %s does not exist.', $originalDir));
                continue;
            }

            $initDir = $rootInitDir . $dir;

            if (($dir == $viewPreprocessedDir)
                && $this->globalConfig->get(GlobalConfig::VAR_SKIP_HTML_MINIFICATION)
            ) {
                $this->logger->notice(sprintf('Skip copying %s->%s', $originalDir, $initDir));
                continue;
            }

            $this->logger->debug(sprintf('Copying %s->%s', $originalDir, $initDir));
            $this->backupDir($originalDir, $initDir);
        }

        $this->backupLogDir($magentoRoot . $logDir, $rootInitDir . $logDir);
    }

    protected function backupLogDir($originalLogDir, $initLogDir)
    {
        $this->logger->debug(sprintf('Copying %s->%s', $originalLogDir, $initLogDir));
        $this->stopLogging();
        $this->backupDir($originalLogDir, $initLogDir);
        $this->restoreLogging();
    }

    /**
     * @param string $source
     * @param string $destination
     */
    private function backupDir(string $source, string $destination)
    {
        $this->file->createDirectory($destination);
        $this->file->copyDirectory($source, $destination);
    }

    /**
     * Removes all log handlers for closing all connections to files that are opened for logging.
     *
     * It's done for avoiding file system exceptions while file opened for writing is not physically exists
     * and some process trying to write into that file.
     */
    private function stopLogging()
    {
        $this->logger->setHandlers([]);
    }

    /**
     * Restore all log handlers.
     */
    private function restoreLogging()
    {
        $this->logger->setHandlers($this->loggerPool->getHandlers());
    }
}
