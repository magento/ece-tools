<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Process\Deploy\InstallUpdate\Update;

use Magento\MagentoCloud\Config\Environment;
use Magento\MagentoCloud\Config\Stage\DeployInterface;
use Magento\MagentoCloud\Filesystem\DirectoryList;
use Magento\MagentoCloud\Filesystem\Flag\Manager as FlagManager;
use Magento\MagentoCloud\Process\ProcessInterface;
use Magento\MagentoCloud\Shell\ExecBinMagento;
use Magento\MagentoCloud\Shell\ShellException;
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
     * @var ExecBinMagento
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
     * @var DeployInterface
     */
    private $stageConfig;

    /**
     * @param LoggerInterface $logger
     * @param Environment $environment
     * @param ExecBinMagento $shell
     * @param DirectoryList $directoryList
     * @param FileList $fileList
     * @param FlagManager $flagManager
     * @param DeployInterface $stageConfig
     */
    public function __construct(
        LoggerInterface $logger,
        Environment $environment,
        ExecBinMagento $shell,
        DirectoryList $directoryList,
        FileList $fileList,
        FlagManager $flagManager,
        DeployInterface $stageConfig
    ) {
        $this->logger = $logger;
        $this->environment = $environment;
        $this->shell = $shell;
        $this->directoryList = $directoryList;
        $this->fileList = $fileList;
        $this->flagManager = $flagManager;
        $this->stageConfig = $stageConfig;
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
            $verbosityLevel = $this->stageConfig->get(DeployInterface::VAR_VERBOSE_COMMANDS);

            $this->logger->notice('Enabling Maintenance mode.');
            $this->shell->execute('maintenance:enable', $verbosityLevel);
            $this->logger->info('Running setup upgrade.');

            $output = $this->shell->execute('setup:upgrade', ['--keep-generated', $verbosityLevel]);

            $this->shell->execute('maintenance:disable', $verbosityLevel);
            $this->logger->notice('Maintenance mode is disabled.');
        } catch (ShellException $e) {
            $output = $e->getOutput();
            //Rollback required by database
            throw new \RuntimeException($e->getMessage(), 6, $e);
        } finally {
            file_put_contents($this->fileList->getInstallUpgradeLog(), $output, FILE_APPEND);
        }

        $this->flagManager->delete(FlagManager::FLAG_REGENERATE);
    }
}
