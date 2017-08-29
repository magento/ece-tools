<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Process\Build;

use Magento\MagentoCloud\Config\Environment;
use Magento\MagentoCloud\Filesystem\Driver\File;
use Magento\MagentoCloud\Process\ProcessInterface;
use Magento\MagentoCloud\Shell\ShellInterface;
use Psr\Log\LoggerInterface;

/**
 *
 * Writable directories will be erased when the writable filesystem is mounted to them. This
 * step backs them up to ./init/
 *
 * {@inheritdoc}
 */
class BackupToInitDirectory implements ProcessInterface
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
     * @var ShellInterface
     */
    private $shell;

    /**
     * @var Environment
     */
    private $environment;

    /**
     * @param File $file
     * @param LoggerInterface $logger
     * @param ShellInterface $shell
     * @param Environment $environment
     */
    public function __construct(File $file, LoggerInterface $logger, ShellInterface $shell, Environment $environment)
    {
        $this->file = $file;
        $this->logger = $logger;
        $this->shell = $shell;
        $this->environment = $environment;
    }

    /**
     * @inheritdoc
     */
    public function execute()
    {
        if ($this->file->isExists(Environment::REGENERATE_FLAG)) {
            $this->logger->info('Removing .regenerate flag');
            $this->file->deleteFile(Environment::REGENERATE_FLAG);
        }

        if ($this->environment->isStaticDeployInBuild()) {
            $this->logger->info('Moving static content to init directory');
            $this->shell->execute('mkdir -p ./init/pub/');

            if ($this->file->isExists('./init/pub/static')) {
                $this->logger->info('Remove ./init/pub/static');
                $this->file->deleteFile('./init/pub/static');
            }
            $this->shell->execute('cp -R ./pub/static/ ./init/pub/static');

            $this->file->copy(
                MAGENTO_ROOT . Environment::STATIC_CONTENT_DEPLOY_FLAG,
                MAGENTO_ROOT . 'init/' . Environment::STATIC_CONTENT_DEPLOY_FLAG
            );
        } else {
            $this->logger->info('No file ' . Environment::STATIC_CONTENT_DEPLOY_FLAG);
        }

        $this->logger->info('Copying writable directories to temp directory.');

        foreach ($this->environment->getWritableDirectories() as $dir) {
            $this->shell->execute(sprintf('mkdir -p init/%s', $dir));
            $this->shell->execute(sprintf('mkdir -p %s', $dir));

            if (count($this->file->scanDir(MAGENTO_ROOT . $dir)) > 2) {
                $this->shell->execute(
                    sprintf('/bin/bash -c "shopt -s dotglob; cp -R %s/* ./init/%s/"', $dir, $dir)
                );
                $this->shell->execute(sprintf('rm -rf %s', $dir));
                $this->shell->execute(sprintf('mkdir -p %s', $dir));
            }
        }
    }
}
