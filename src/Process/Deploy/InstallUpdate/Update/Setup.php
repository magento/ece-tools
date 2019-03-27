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
use Magento\MagentoCloud\Process\ProcessException;
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
     * @var DeployInterface
     */
    private $stageConfig;

    /**
     * @param LoggerInterface $logger
     * @param Environment $environment
     * @param ShellInterface $shell
     * @param DirectoryList $directoryList
     * @param FileList $fileList
     * @param FlagManager $flagManager
     * @param DeployInterface $stageConfig
     */
    public function __construct(
        LoggerInterface $logger,
        Environment $environment,
        ShellInterface $shell,
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
     * @inheritdoc
     */
    public function execute()
    {
        $this->flagManager->delete(FlagManager::FLAG_REGENERATE);

        try {
            $verbosityLevel = $this->stageConfig->get(DeployInterface::VAR_VERBOSE_COMMANDS);
            $installUpgradeLog = $this->fileList->getInstallUpgradeLog();

            $this->logger->info('Running setup upgrade.');

            $this->shell->execute('echo \'Updating time: \'$(date) | tee -a ' . $installUpgradeLog);
            $this->shell->execute(sprintf(
                '/bin/bash -c "set -o pipefail; %s | tee -a %s"',
                'php ./bin/magento setup:upgrade --keep-generated --ansi --no-interaction ' . $verbosityLevel,
                $installUpgradeLog
            ));
        } catch (\RuntimeException $exception) {
            //Rollback required by database
            throw new ProcessException($exception->getMessage(), 6, $exception);
        }

        $this->flagManager->delete(FlagManager::FLAG_REGENERATE);
    }
}
