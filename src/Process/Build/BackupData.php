<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Process\Build;

use Magento\MagentoCloud\App\Logger\Pool as LoggerPool;
use Magento\MagentoCloud\Filesystem\DirectoryList;
use Magento\MagentoCloud\Filesystem\Driver\File;
use Magento\MagentoCloud\Filesystem\Flag\Manager as FlagManager;
use Magento\MagentoCloud\Process\ProcessInterface;
use Psr\Log\LoggerInterface;

/**
 * Writable directories will be erased when the writable filesystem is mounted to them. This
 * step backs them up to ./init/
 *
 * {@inheritdoc}
 */
class BackupData implements ProcessInterface
{
    /**
     * @var File
     */
    private $file;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var DirectoryList
     */
    private $directoryList;

    /**
     * @var FlagManager
     */
    private $flagManager;

    /**
     * @var LoggerPool
     */
    private $loggerPool;

    /**
     * BackupData constructor.
     *
     * @param File $file
     * @param LoggerInterface $logger
     * @param DirectoryList $directoryList
     * @param FlagManager $flagManager
     * @param LoggerPool $loggerPool
     */
    public function __construct(
        File $file,
        LoggerInterface $logger,
        DirectoryList $directoryList,
        FlagManager $flagManager,
        LoggerPool $loggerPool
    ) {
        $this->file = $file;
        $this->logger = $logger;
        $this->directoryList = $directoryList;
        $this->flagManager = $flagManager;
        $this->loggerPool = $loggerPool;
    }

    /**
     * @inheritdoc
     */
    public function execute()
    {
        $magentoRoot = $this->directoryList->getMagentoRoot() . '/';
        $rootInitDir = $this->directoryList->getInit() . '/';
        $this->flagManager->delete(FlagManager::FLAG_REGENERATE);

        if ($this->flagManager->exists(FlagManager::FLAG_STATIC_CONTENT_DEPLOY_IN_BUILD)) {
            $scdFlagPath = $this->flagManager->getFlagPath(FlagManager::FLAG_STATIC_CONTENT_DEPLOY_IN_BUILD);
            $initPub = $rootInitDir . 'pub/';
            $initPubStatic = $initPub . 'static/';
            $originalPubStatic = $magentoRoot . 'pub/static/';

            $this->logger->info('Moving static content to init directory');
            $this->file->createDirectory($initPub);

            if ($this->file->isExists($initPubStatic)) {
                $this->logger->info('Remove ./init/pub/static');
                $this->file->deleteDirectory($initPubStatic);
            }

            $this->file->createDirectory($initPubStatic);
            $this->file->copyDirectory($originalPubStatic, $initPubStatic);
            $this->file->copy(
                $magentoRoot . $scdFlagPath,
                $rootInitDir . $scdFlagPath
            );
        } else {
            $this->logger->info('SCD not performed during build');
        }

        $this->logger->info('Copying writable directories to temp directory.');

        $this->stopLogging();

        foreach ($this->directoryList->getWritableDirectories() as $originalDir) {
            $initDir = $rootInitDir . substr($originalDir, strlen($magentoRoot));

            $this->file->createDirectory($initDir);
            $this->file->createDirectory($originalDir);

            if (count($this->file->scanDir($originalDir)) > 2) {
                $this->file->copyDirectory($originalDir, $initDir);
                $this->file->deleteDirectory($originalDir);
                $this->file->createDirectory($originalDir);
            }
        }

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
