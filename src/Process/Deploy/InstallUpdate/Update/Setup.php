<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Process\Deploy\InstallUpdate\Update;

use Magento\MagentoCloud\Config\Environment;
use Magento\MagentoCloud\Filesystem\DirectoryList;
use Magento\MagentoCloud\Filesystem\Flag\Manager as FlagManager;
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
     * @var FlagManager
     */

    private $flagManager;

    /**
     * @var FileList
     */
    private $fileList;

    /**
     * @var DirectoryList
     */
    private $directoryList;

    /**
     * Setup constructor.
     * @param LoggerInterface $logger
     * @param Environment $environment
     * @param ShellInterface $shell
     * @param DirectoryList $directoryList
     * @param FileList $fileList
     * @param FlagManager $flagManager
     */
    public function __construct(
        LoggerInterface $logger,
        Environment $environment,
        ShellInterface $shell,
        DirectoryList $directoryList,
        FileList $fileList,
        FlagManager $flagManager
    ) {
        $this->logger = $logger;
        $this->environment = $environment;
        $this->shell = $shell;
        $this->directoryList = $directoryList;
        $this->fileList = $fileList;
        $this->flagManager = $flagManager;
    }

    /**
     * Executes the process.
     *
     * @return void
     */
    public function execute()
    {
        $this->flagManager->delete(FlagManager::FLAG_REGENERATE);

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

        $this->flagManager->delete(FlagManager::FLAG_REGENERATE);
    }
}
