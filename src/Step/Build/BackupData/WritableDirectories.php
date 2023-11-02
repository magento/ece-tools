<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Step\Build\BackupData;

use Magento\MagentoCloud\App\Error;
use Magento\MagentoCloud\App\GenericException;
use Magento\MagentoCloud\App\Logger\Pool as LoggerPool;
use Magento\MagentoCloud\App\LoggerException;
use Magento\MagentoCloud\Config\GlobalSection as GlobalConfig;
use Magento\MagentoCloud\Filesystem\DirectoryList;
use Magento\MagentoCloud\Filesystem\Driver\File;
use Magento\MagentoCloud\Filesystem\FileSystemException;
use Magento\MagentoCloud\Step\StepException;
use Magento\MagentoCloud\Step\StepInterface;
use Magento\MagentoCloud\App\Logger;

/**
 * Writable directories will be erased when the writable filesystem is mounted to them.
 * This step backs them up to ./init/
 *
 * {@inheritdoc}
 */
class WritableDirectories implements StepInterface
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
     * @var Logger
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
     * @param Logger $logger
     * @param LoggerPool $loggerPool
     */
    public function __construct(
        File $file,
        DirectoryList $directoryList,
        GlobalConfig $globalConfig,
        Logger $logger,
        LoggerPool $loggerPool
    ) {
        $this->file = $file;
        $this->directoryList = $directoryList;
        $this->globalConfig = $globalConfig;
        $this->logger = $logger;
        $this->loggerPool = $loggerPool;
    }

    /**
     * @inheritdoc
     */
    public function execute()
    {
        try {
            $magentoRoot = $this->directoryList->getMagentoRoot() . '/';
            $rootInitDir = $this->directoryList->getPath(DirectoryList::DIR_INIT) . '/';
            $viewPreprocessedDir = $this->directoryList->getPath(DirectoryList::DIR_VIEW_PREPROCESSED, true);
            $logDir = $this->directoryList->getPath(DirectoryList::DIR_LOG, true);
            $writableDirectories = $this->directoryList->getWritableDirectories();

            $this->logger->info(sprintf('Copying writable directories to %s directory.', $rootInitDir));

            foreach ($writableDirectories as $dir) {
                if ($dir === $logDir) {
                    continue;
                }

                $originalDir = $magentoRoot . $dir;

                if (!$this->file->isExists($originalDir)) {
                    $this->logger->notice(sprintf('Directory %s does not exist.', $originalDir));
                    continue;
                }

                $initDir = $rootInitDir . $dir;

                if (($dir === $viewPreprocessedDir)
                    && $this->globalConfig->get(GlobalConfig::VAR_SKIP_HTML_MINIFICATION)
                ) {
                    $this->logger->notice(sprintf('Skip copying %s->%s', $originalDir, $initDir));
                    continue;
                }

                $this->logger->debug(sprintf('Copying %s->%s', $originalDir, $initDir));
                $this->backupDir($originalDir, $initDir);
            }

            $this->backupLogDir($magentoRoot . $logDir, $rootInitDir . $logDir);
        } catch (StepException $e) {
            throw $e;
        } catch (GenericException $e) {
            new StepException($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * Stops logging and restores it after backup directory.
     *
     * @param string $originalLogDir
     * @param string $initLogDir
     * @throws StepException
     */
    private function backupLogDir(string $originalLogDir, string $initLogDir)
    {
        try {
            $this->logger->debug(sprintf('Copying %s->%s', $originalLogDir, $initLogDir));
            $this->logger->setHandlers([]);
            $this->backupDir($originalLogDir, $initLogDir);
            $this->logger->setHandlers($this->loggerPool->getHandlers());
        } catch (LoggerException $e) {
            throw new StepException($e->getMessage(), Error::BUILD_UNABLE_TO_CREATE_LOGGER, $e);
        }
    }

    /**
     * @param string $source
     * @param string $destination
     * @throws StepException
     */
    private function backupDir(string $source, string $destination)
    {
        try {
            $this->file->createDirectory($destination);
            $this->file->copyDirectory($source, $destination);
        } catch (FileSystemException $e) {
            throw new StepException($e->getMessage(), Error::BUILD_WRITABLE_DIRECTORY_COPYING_FAILED, $e);
        }
    }
}
