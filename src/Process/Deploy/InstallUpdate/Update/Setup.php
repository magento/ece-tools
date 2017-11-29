<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Process\Deploy\InstallUpdate\Update;

use Magento\MagentoCloud\Config\Environment;
use Magento\MagentoCloud\Filesystem\DirectoryList;
use Magento\MagentoCloud\Filesystem\Driver\File;
use Magento\MagentoCloud\Process\ProcessInterface;
use Magento\MagentoCloud\Shell\ShellInterface;
use Magento\MagentoCloud\Filesystem\FileList;
use Psr\Log\LoggerInterface;

/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
class Setup implements ProcessInterface
{
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var Environment
     */
    private $environment;

    /**
     * @var ShellInterface
     */
    private $shell;

    /**
     * @var File
     */
    private $file;

    /**
     * @var FileList
     */
    private $fileList;

    /**
     * @var DirectoryList
     */
    private $directoryList;

    /**
     * @param LoggerInterface $logger
     * @param Environment $environment
     * @param ShellInterface $shell
     * @param File $file
     * @param DirectoryList $directoryList
     * @param FileList $fileList
     */
    public function __construct(
        LoggerInterface $logger,
        Environment $environment,
        ShellInterface $shell,
        File $file,
        DirectoryList $directoryList,
        FileList $fileList
    ) {
        $this->logger = $logger;
        $this->environment = $environment;
        $this->shell = $shell;
        $this->file = $file;
        $this->directoryList = $directoryList;
        $this->fileList = $fileList;
    }

    /**
     * Executes the process.
     *
     * @return void
     */
    public function execute()
    {
        $this->removeRegenerateFlag();

        try {
            $verbosityLevel = $this->environment->getVerbosityLevel();
            /* Enable maintenance mode */
            $this->logger->notice('Enabling Maintenance mode.');
            $this->shell->execute('php ./bin/magento maintenance:enable ' . $verbosityLevel);

            $this->logger->info('Running setup upgrade.');

            $this->shell->execute(sprintf(
                '/bin/bash -c "set -o pipefail; %s | tee -a %s"',
                'php ./bin/magento setup:upgrade --keep-generated -n ' . $verbosityLevel,
                $this->fileList->getInstallUpgradeLog()
            ));

            /* Disable maintenance mode */
            $this->shell->execute('php ./bin/magento maintenance:disable ' . $verbosityLevel);
            $this->logger->notice('Maintenance mode is disabled.');
        } catch (\RuntimeException $e) {
            //Rollback required by database
            throw new \RuntimeException($e->getMessage(), 6);
        }

        $this->removeRegenerateFlag();
    }

    /**
     * Removes regenerate flag file if such file exists
     */
    private function removeRegenerateFlag()
    {
        $magentoRoot = $this->directoryList->getMagentoRoot();

        if ($this->file->isExists($magentoRoot . '/' . Environment::REGENERATE_FLAG)) {
            $this->logger->info('Removing .regenerate flag');
            $this->file->deleteFile($magentoRoot . '/' . Environment::REGENERATE_FLAG);
        }
    }
}
