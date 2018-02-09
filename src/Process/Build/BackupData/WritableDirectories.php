<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Process\Build\BackupData;

use Magento\MagentoCloud\Config\Stage\BuildInterface;
use Magento\MagentoCloud\Filesystem\DirectoryList;
use Magento\MagentoCloud\Filesystem\Driver\File;
use Magento\MagentoCloud\Process\ProcessInterface;
use Psr\Log\LoggerInterface;

/**
 * Copies writable directories to ./init
 */
class WritableDirectories implements ProcessInterface
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
     * @var BuildInterface
     */
    private $stageConfig;

    /**
     * BackupWritableDirectories constructor.
     * @param File $file
     * @param LoggerInterface $logger
     * @param DirectoryList $directoryList
     * @param BuildInterface $stageConfig
     */
    public function __construct(
        File $file,
        LoggerInterface $logger,
        DirectoryList $directoryList,
        BuildInterface $stageConfig
    ) {
        $this->file = $file;
        $this->logger = $logger;
        $this->directoryList = $directoryList;
        $this->stageConfig = $stageConfig;
    }

    public function execute()
    {
        $this->logger->info('Copying writable directories to temp directory.');

        $writableDirectories = $this->directoryList->getWritableDirectories();

        if ($this->stageConfig->get(BuildInterface::VAR_SKIP_COPYING_VIEW_PREPROCESSED_DIR)
            && ($key = array_search('var/view_preprocessed', $writableDirectories)) !== false
        ) {
            unset($writableDirectories[$key]);
        }

        $magentoRoot = $this->directoryList->getMagentoRoot() . '/';
        $rootInitDir = $this->directoryList->getInit() . '/';

        foreach ($writableDirectories as $dir) {
            $originalDir = $magentoRoot . $dir;
            if ($this->file->isExists($originalDir)) {
                $initDir = $rootInitDir . $dir;
                $this->file->createDirectory($initDir);
                $this->file->copyDirectory($originalDir, $initDir);
            }
        }
    }
}
