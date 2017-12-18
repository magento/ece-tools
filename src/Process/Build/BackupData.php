<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Process\Build;

use Magento\MagentoCloud\App\Logger\Pool as LoggerPool;
use Magento\MagentoCloud\Config\Environment;
use Magento\MagentoCloud\Filesystem\DirectoryList;
use Magento\MagentoCloud\Filesystem\Driver\File;
use Magento\MagentoCloud\Filesystem\FlagFile\Flag\Regenerate;
use Magento\MagentoCloud\Filesystem\FlagFile\Flag\StaticContentDeployInBuild;
use Magento\MagentoCloud\Filesystem\FlagFile\Manager as FlagFileManager;
use Magento\MagentoCloud\Process\ProcessInterface;
use Psr\Log\LoggerInterface;

/**
 *
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
     * @var Environment
     */
    private $environment;

    /**
     * @var DirectoryList
     */
    private $directoryList;

    /**
     * @var FlagFileManager
     */
    private $flagFileManager;

    /**
     * @var LoggerPool
     */
    private $loggerPool;

    /**
     * BackupData constructor.
     *
     * @param File $file
     * @param LoggerInterface $logger
     * @param Environment $environment
     * @param DirectoryList $directoryList
     * @param FlagFileManager $flagFileManager
     * @param LoggerPool $loggerPool
     */
    public function __construct(
        File $file,
        LoggerInterface $logger,
        Environment $environment,
        DirectoryList $directoryList,
        FlagFileManager $flagFileManager,
        LoggerPool $loggerPool
    ) {
        $this->file = $file;
        $this->logger = $logger;
        $this->environment = $environment;
        $this->directoryList = $directoryList;
        $this->flagFileManager = $flagFileManager;
        $this->loggerPool = $loggerPool;
    }

    /**
     * @inheritdoc
     */
    public function execute()
    {
        $magentoRoot = $this->directoryList->getMagentoRoot() . '/';
        $rootInitDir = $this->directoryList->getInit() . '/';
        $this->flagFileManager->delete(Regenerate::KEY);

        if ($this->flagFileManager->exists(StaticContentDeployInBuild::KEY)) {
            $scdFlag = $this->flagFileManager->getFlag(StaticContentDeployInBuild::KEY);
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
                $magentoRoot . $scdFlag->getPath(),
                $rootInitDir . $scdFlag->getPath()
            );
        } else {
            $this->logger->info('SCD not performed during build');
        }

        $this->logger->info('Copying writable directories to temp directory.');

        $this->stopLogging();

        foreach ($this->environment->getWritableDirectories() as $dir) {
            $originalDir = $magentoRoot . $dir;
            $initDir = $rootInitDir . $dir;

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
