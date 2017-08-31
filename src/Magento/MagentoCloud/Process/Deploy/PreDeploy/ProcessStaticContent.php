<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Process\Deploy\PreDeploy;

use Magento\MagentoCloud\Config\Environment;
use Magento\MagentoCloud\Filesystem\DirectoryList;
use Magento\MagentoCloud\Filesystem\Driver\File;
use Magento\MagentoCloud\Filesystem\FileSystemException;
use Magento\MagentoCloud\Process\ProcessInterface;
use Magento\MagentoCloud\Shell\ShellInterface;
use Magento\MagentoCloud\Util\BuildDirCopier;
use Magento\MagentoCloud\Util\StaticContentCleaner;
use Psr\Log\LoggerInterface;

class ProcessStaticContent implements ProcessInterface
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
     * @var StaticContentCleaner
     */
    private $staticContentCleaner;

    /**
     * @var File
     */
    private $file;
    /**
     * @var BuildDirCopier
     */
    private $buildDirCopier;

    /**
     * @var DirectoryList
     */
    private $directoryList;

    /**
     * @param LoggerInterface $logger
     * @param ShellInterface $shell
     * @param Environment $env
     * @param StaticContentCleaner $staticContentCleaner
     * @param File $file
     * @param BuildDirCopier $buildDirCopier
     * @param DirectoryList $directoryList
     */
    public function __construct(
        LoggerInterface $logger,
        ShellInterface $shell,
        Environment $env,
        StaticContentCleaner $staticContentCleaner,
        File $file,
        BuildDirCopier $buildDirCopier,
        DirectoryList $directoryList
    ) {
        $this->logger = $logger;
        $this->shell = $shell;
        $this->env = $env;
        $this->staticContentCleaner = $staticContentCleaner;
        $this->file = $file;
        $this->buildDirCopier = $buildDirCopier;
        $this->directoryList = $directoryList;
    }

    /**
     * Handle case where static content is deployed during build hook:
     *  1. set a flag to be read by magento-cloud:deploy
     *  2. Either copy or symlink files from init/ directory, depending on strategy
     *
     * @inheritdoc
     */
    public function execute()
    {
        if (!$this->env->isStaticDeployInBuild()) {
            return;
        }

        $this->logger->info("Static content deployment was performed during build hook");
        $this->staticContentCleaner->clean();

        if ($this->env->isStaticContentSymlinkOn()) {
            $this->logger->info("Symlinking static content from pub/static to init/pub/static");
            $this->symlinkStaticContent();
        } else {
            $this->logger->info('Copying static content from init/pub/static to pub/static');
            $this->buildDirCopier->copy('pub/static');
        }
    }

    /**
     * Creates symlinks for static content pub/static => init/pub/static
     *
     * @return void
     */
    private function symlinkStaticContent()
    {
        $magentoRoot = $this->directoryList->getMagentoRoot();

        // Symlink pub/static/* to init/pub/static/*
        $staticContentLocation = $this->file->getRealPath($magentoRoot . '/pub/static') . '/';
        $buildDir = $this->file->getRealPath($magentoRoot . '/init') . '/';
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
