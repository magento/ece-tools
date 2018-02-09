<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Process\Build;

use Magento\MagentoCloud\App\Logger\Pool as LoggerPool;
use Magento\MagentoCloud\Process\Build\BackupData\StaticContent;
use Magento\MagentoCloud\Process\Build\BackupData\WritableDirectories;
use Magento\MagentoCloud\Process\ProcessInterface;
use Psr\Log\LoggerInterface;
use Monolog\Logger;

/**
 * Writable directories will be erased when the writable filesystem is mounted to them. This
 * step backs them up to ./init/
 *
 * {@inheritdoc}
 */
class BackupData implements ProcessInterface
{
    /**
     * @var StaticContent
     */
    private $backupStaticContentProcess;

    /**
     * @var WritableDirectories
     */
    private $backupWritableDirectoriesProcess;
    /**
     * @var LoggerInterface|Logger
     */
    private $logger;

    /**
     * @var LoggerPool
     */
    private $loggerPool;

    /**
     * BackupData constructor.
     *
     * @param StaticContent $backupStaticContentProcess
     * @param WritableDirectories $backupWritableDirectories
     * @param LoggerInterface $logger
     * @param LoggerPool $loggerPool
     */
    public function __construct(
        StaticContent $backupStaticContentProcess,
        WritableDirectories $backupWritableDirectories,
        LoggerInterface $logger,
        LoggerPool $loggerPool
    ) {
        $this->backupStaticContentProcess = $backupStaticContentProcess;
        $this->backupWritableDirectoriesProcess = $backupWritableDirectories;
        $this->logger = $logger;
        $this->loggerPool = $loggerPool;
    }

    /**
     * @inheritdoc
     */
    public function execute()
    {
        $this->backupStaticContentProcess->execute();
        $this->stopLogging();
        $this->backupWritableDirectoriesProcess->execute();
        $this->restoreLogging();
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
