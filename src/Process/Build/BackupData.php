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
     * @var LoggerPool
     */
    private $loggerPool;

    /**
     * @param File $file
     * @param LoggerInterface $logger
     * @param Environment $environment
     * @param DirectoryList $directoryList
     * @param LoggerPool $loggerPool
     */
    public function __construct(
        File $file,
        LoggerInterface $logger,
        Environment $environment,
        DirectoryList $directoryList,
        LoggerPool $loggerPool
    ) {
        $this->file = $file;
        $this->logger = $logger;
        $this->environment = $environment;
        $this->directoryList = $directoryList;
        $this->loggerPool = $loggerPool;
    }

    /**
     * @inheritdoc
     */
    public function execute()
    {
        $magentoRoot = $this->directoryList->getMagentoRoot() . '/';
        $rootInitDir = $this->directoryList->getInit() . '/';

        if ($this->file->isExists($magentoRoot . Environment::REGENERATE_FLAG)) {
            $this->logger->info('Removing .regenerate flag');
            $this->file->deleteFile($magentoRoot . Environment::REGENERATE_FLAG);
        }

        if ($this->environment->isStaticDeployInBuild()) {
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
                $magentoRoot . Environment::STATIC_CONTENT_DEPLOY_FLAG,
                $rootInitDir . Environment::STATIC_CONTENT_DEPLOY_FLAG
            );
        } else {
            $this->logger->info('No file ' . Environment::STATIC_CONTENT_DEPLOY_FLAG);
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
