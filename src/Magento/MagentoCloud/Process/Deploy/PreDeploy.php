<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Process\Deploy;

use Magento\MagentoCloud\Filesystem\FileSystemException;
use Magento\MagentoCloud\Process\ProcessInterface;
use Magento\MagentoCloud\Shell\ShellInterface;
use Magento\MagentoCloud\Filesystem\Driver\File;
use Magento\MagentoCloud\Config\Environment;
use Magento\MagentoCloud\Util\ComponentInfo;
use Magento\MagentoCloud\Util\StaticContentCleaner;
use Psr\Log\LoggerInterface;

/**
 * @inheritdoc
 */
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
     * @var ComponentInfo
     */
    private $componentInfo;

    /**
     * @var StaticContentCleaner
     */
    private $staticContentCleaner;

    /**
     * @var ProcessInterface
     */
    private $process;

    /**
     * @param LoggerInterface $logger
     * @param ProcessInterface $process
     * @param Environment $env
     * @param ShellInterface $shell
     * @param File $file
     * @param ComponentInfo $componentInfo
     * @param StaticContentCleaner $staticContentCleaner
     */
    public function __construct(
        LoggerInterface $logger,
        ProcessInterface $process,
        Environment $env,
        ShellInterface $shell,
        File $file,
        ComponentInfo $componentInfo,
        StaticContentCleaner $staticContentCleaner
    ) {
        $this->env = $env;
        $this->logger = $logger;
        $this->shell = $shell;
        $this->file = $file;
        $this->componentInfo = $componentInfo;
        $this->staticContentCleaner = $staticContentCleaner;
        $this->process = $process;
    }

    /**
     * @inheritdoc
     */
    public function execute()
    {
        $this->logger->info('Starting predeploy. ' . $this->componentInfo->get());
        $this->process->execute();

        /**
         * Handle case where static content is deployed during build hook:
         *  1. set a flag to be read by magento-cloud:deploy
         *  2. Either copy or symlink files from init/ directory, depending on strategy
         */
        if ($this->env->isStaticDeployInBuild()) {
            $this->logger->info("Static content deployment was performed during build hook");
            $this->staticContentCleaner->clean();

            if ($this->env->isStaticContentSymlinkOn()) {
                $this->logger->info("Symlinking static content from pub/static to init/pub/static");
                $this->symlinkStaticContent();
            } else {
                $this->logger->info('Copying static content from init/pub/static to pub/static');
                $this->copyFromBuildDir('pub/static');
            }
        }

        // Restore mounted directories
        $this->logger->info('Copying writable directories back.');
        $mountedDirectories = ['app/etc', 'pub/media'];
        foreach ($mountedDirectories as $dir) {
            $this->copyFromBuildDir($dir);
        }

        if ($this->file->isExists(Environment::REGENERATE_FLAG)) {
            $this->logger->info('Removing var/.regenerate flag');
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

    /**
     * Creates symlinks for static content pub/static => init/pub/static
     *
     * @return void
     */
    private function symlinkStaticContent()
    {
        // Symlink pub/static/* to init/pub/static/*
        $staticContentLocation = $this->file->getRealPath(MAGENTO_ROOT . 'pub/static') . '/';
        $buildDir = $this->file->getRealPath(MAGENTO_ROOT . 'init') . '/';
        if ($this->file->isExists($buildDir . 'pub/static')) {
            $dir = new \DirectoryIterator($buildDir . 'pub/static');
            foreach ($dir as $fileInfo) {
                if ($fileInfo->isDot()) {
                    continue;
                }

                $fromDir = $buildDir . 'pub/static/' . $fileInfo->getFilename();
                $toDir = $staticContentLocation . '/' . $fileInfo->getFilename();

                try {
                    if ($this->file->symlink($fromDir, $toDir)) {
                        $this->logger->info(sprintf('Create symlink %s => %s', $toDir, $fromDir));
                    }
                } catch (FileSystemException $e) {
                    $this->logger->error($e->getMessage());
                }
            }
        }
    }
}
