<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Process\Build;

use Magento\MagentoCloud\Config\Environment;
use Magento\MagentoCloud\Filesystem\DirectoryList;
use Magento\MagentoCloud\Filesystem\Driver\File;
use Magento\MagentoCloud\Filesystem\FlagFilePool;
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
     * FlagFilePool
     */
    private $flagFilePool;

    /**
     * BackupData constructor.
     * @param File $file
     * @param LoggerInterface $logger
     * @param Environment $environment
     * @param DirectoryList $directoryList
     * @param FlagFilePool $flagFilePool
     */
    public function __construct(
        File $file,
        LoggerInterface $logger,
        Environment $environment,
        DirectoryList $directoryList,
        FlagFilePool $flagFilePool
    ) {
        $this->file = $file;
        $this->logger = $logger;
        $this->environment = $environment;
        $this->directoryList = $directoryList;
        $this->flagFilePool = $flagFilePool;
    }

    /**
     * @inheritdoc
     */
    public function execute()
    {
        $magentoRoot = $this->directoryList->getMagentoRoot() . '/';
        $rootInitDir = $this->directoryList->getInit() . '/';
        $regenerateFlag = $this->flagFilePool->getFlag(FlagFilePool::REGENERATE_FLAG);
        $scdFlag = $this->flagFilePool->getFlag(FlagFilePool::SCD_IN_BUILD_FLAG);

        $regenerateFlag->delete();

        if ($scdFlag->exists()) {
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
    }
}
