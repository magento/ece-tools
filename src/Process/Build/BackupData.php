<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Process\Build;

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
     * @param File $file
     * @param LoggerInterface $logger
     * @param Environment $environment
     * @param DirectoryList $directoryList
     */
    public function __construct(
        File $file,
        LoggerInterface $logger,
        Environment $environment,
        DirectoryList $directoryList
    ) {
        $this->file = $file;
        $this->logger = $logger;
        $this->environment = $environment;
        $this->directoryList = $directoryList;
    }

    /**
     * @inheritdoc
     */
    public function execute()
    {
        $magentoRoot = $this->directoryList->getMagentoRoot() . '/';

        if ($this->file->isExists($magentoRoot . Environment::REGENERATE_FLAG)) {
            $this->logger->info('Removing .regenerate flag');
            $this->file->deleteFile($magentoRoot . Environment::REGENERATE_FLAG);
        }

        if ($this->environment->isStaticDeployInBuild()) {
            $initPub = $magentoRoot . 'init/pub/';
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
                $magentoRoot . 'init/' . Environment::STATIC_CONTENT_DEPLOY_FLAG
            );
        } else {
            $this->logger->info('No file ' . Environment::STATIC_CONTENT_DEPLOY_FLAG);
        }

        $this->logger->info('Copying writable directories to temp directory.');

        foreach ($this->environment->getWritableDirectories() as $dir) {
            $originalDir = $magentoRoot . $dir;
            $initDir = $magentoRoot . 'init/' . $dir;

            $this->file->createDirectory($initDir);
            $this->file->createDirectory($originalDir);

            if (count($this->file->scanDir($originalDir)) > 2) {
                $this->file->copyDirectory($originalDir, $initDir);
                if ($this->environment->isPlatformEnv()) { // if this is running on cloud filesystem (not local)
                    // not sure why deleting and recreating?
                    $this->file->deleteDirectory($originalDir);
                    $this->file->createDirectory($originalDir);
                }
            }
        }
    }
}
