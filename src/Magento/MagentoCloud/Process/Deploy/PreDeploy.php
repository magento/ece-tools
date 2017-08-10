<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Process\Deploy;

use Magento\MagentoCloud\Process\ProcessInterface;
use Magento\MagentoCloud\Shell\ShellInterface;
use Magento\MagentoCloud\Filesystem\Driver\File;
use Magento\MagentoCloud\Config\Environment;
use Psr\Log\LoggerInterface;

class PreDeploy implements ProcessInterface
{
    /**
     * @var Environment
     */
    private $env;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var ShellInterface
     */
    private $shell;

    /**
     * @var File
     */
    private $file;

    /**
     * @param Environment $env
     * @param LoggerInterface $logger
     * @param ShellInterface $shell
     * @param File $file
     */
    public function __construct(
        Environment $env,
        LoggerInterface $logger,
        ShellInterface $shell,
        File $file
    ) {
        $this->env = $env;
        $this->logger = $logger;
        $this->shell = $shell;
        $this->file = $file;
    }

    /**
     * @inheritdoc
     */
    public function execute()
    {
        $this->logger->info($this->env->startingMessage("pre-deploy"));
        $redis = $this->env->getRelationship('redis');

        if (count($redis) > 0) {
            $redisHost = $redis[0]['host'];
            $redisPort = $redis[0]['port'];
            $redisCacheDb = '1'; // Matches \Magento\MagentoCloud\Command\Deploy::$redisCacheDb
            $this->shell->execute("redis-cli -h $redisHost -p $redisPort -n $redisCacheDb flushdb");
        }

        $fileCacheDir = MAGENTO_ROOT . '/var/cache';
        if ($this->file->isExists($fileCacheDir)) {
            $this->shell->execute("rm -rf $fileCacheDir");
        }

        $mountedDirectories = ['app/etc', 'pub/media'];

        $buildDir = $this->file->getRealPath(MAGENTO_ROOT . 'init') . '/';

        /**
         * Handle case where static content is deployed during build hook:
         *  1. set a flag to be read by magento-cloud:deploy
         *  2. Either copy or symlink files from init/ directory, depending on strategy
         */
        if ($this->file->isExists(MAGENTO_ROOT . Environment::STATIC_CONTENT_DEPLOY_FLAG)) {
            $this->logger->info("Static content deployment was performed during build hook");
            $this->env->removeStaticContent();

            if ($this->env->isStaticContentSymlinkOn()) {
                $this->logger->info("Symlinking static content from pub/static to init/pub/static");

                // Symlink pub/static/* to init/pub/static/*
                $staticContentLocation = $this->file->getRealPath(MAGENTO_ROOT . 'pub/static') . '/';
                if ($this->file->isExists($buildDir . 'pub/static')) {
                    $dir = new \DirectoryIterator($buildDir . 'pub/static');
                    foreach ($dir as $fileInfo) {
                        $fileName = $fileInfo->getFilename();
                        if (!$fileInfo->isDot()
                            && symlink(
                                $buildDir . 'pub/static/' . $fileName,
                                $staticContentLocation . '/' . $fileName
                            )
                        ) {
                            // @codingStandardsIgnoreStart
                            $this->logger->info('Symlinked ' . $staticContentLocation . '/' . $fileName . ' to ' . $buildDir . 'pub/static/' . $fileName);
                            // @codingStandardsIgnoreEnd
                        }
                    }
                }
            } else {
                $this->logger->info("Copying static content from init/pub/static to pub/static");
                $this->copyFromBuildDir('pub/static');
            }
        }

        // Restore mounted directories
        $this->logger->info("Copying writable directories back.");

        foreach ($mountedDirectories as $dir) {
            $this->copyFromBuildDir($dir);
        }

        if ($this->file->isExists(Environment::REGENERATE_FLAG)) {
            $this->logger->info("Removing var/.regenerate flag");
            $this->file->deleteFile(Environment::REGENERATE_FLAG);
        }
    }

    /**
     * @param string $dir The directory to copy. Pass in its normal location relative to Magento root with no prepending
     *                    or trailing slashes
     */
    private function copyFromBuildDir($dir)
    {
        $fullPathDir = MAGENTO_ROOT . $dir;
        if (!$this->file->isExists($fullPathDir)) {
            $this->file->createDirectory($fullPathDir);
            $this->logger->info(sprintf('Created directory: %s', $dir));
        }
        $this->shell->execute(sprintf('/bin/bash -c "shopt -s dotglob; cp -R ./init/%s/* %s/ || true"', $dir, $dir));
        $this->logger->info(sprintf('Copied directory: %s', $dir));
    }
}
