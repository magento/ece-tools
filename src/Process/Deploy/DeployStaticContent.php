<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Process\Deploy;

use Magento\MagentoCloud\Config\Environment;
use Magento\MagentoCloud\Config\Stage\DeployInterface;
use Magento\MagentoCloud\Filesystem\DirectoryList;
use Magento\MagentoCloud\Filesystem\Driver\File;
use Magento\MagentoCloud\Filesystem\Flag\Manager as FlagManager;
use Magento\MagentoCloud\Process\ProcessInterface;
use Magento\MagentoCloud\Util\RemoteDiskIdentifier;
use Psr\Log\LoggerInterface;

/**
 * @inheritdoc
 */
class DeployStaticContent implements ProcessInterface
{
    /**
     * @var ProcessInterface
     */
    private $process;

    /**
     * @var Environment
     */
    private $environment;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var File
     */
    private $file;

    /**
     * @var DirectoryList
     */
    private $directoryList;

    /**
     * @var RemoteDiskIdentifier
     */
    private $remoteDiskIdentifier;

    /**
     * @var FlagManager
     */
    private $flagManager;

    /**
     * @var DeployInterface
     */
    private $stageConfig;

    /**
     * @param ProcessInterface $process
     * @param Environment $environment
     * @param LoggerInterface $logger
     * @param File $file
     * @param DirectoryList $directoryList
     * @param RemoteDiskIdentifier $remoteDiskIdentifier
     * @param FlagManager $flagManager
     * @param DeployInterface $stageConfig
     */
    public function __construct(
        ProcessInterface $process,
        Environment $environment,
        LoggerInterface $logger,
        File $file,
        DirectoryList $directoryList,
        RemoteDiskIdentifier $remoteDiskIdentifier,
        FlagManager $flagManager,
        DeployInterface $stageConfig
    ) {
        $this->process = $process;
        $this->environment = $environment;
        $this->logger = $logger;
        $this->file = $file;
        $this->directoryList = $directoryList;
        $this->remoteDiskIdentifier = $remoteDiskIdentifier;
        $this->flagManager = $flagManager;
        $this->stageConfig = $stageConfig;
    }

    /**
     * This function deploys the static content.
     * Moved this from processMagentoMode() to its own function because we changed the order to have
     * processMagentoMode called before the install.  Static content deployment still needs to happen after install.
     *
     * {@inheritdoc}
     */
    public function execute()
    {
        $this->flagManager->delete(FlagManager::FLAG_STATIC_CONTENT_DEPLOY_PENDING);
        if ($this->remoteDiskIdentifier->isOnLocalDisk('pub/static')
            && !$this->flagManager->exists(FlagManager::FLAG_STATIC_CONTENT_DEPLOY_IN_BUILD)
        ) {
            $this->flagManager->set(FlagManager::FLAG_STATIC_CONTENT_DEPLOY_PENDING);
            $this->logger->info('Postpone static content deployment until prestart');

            return;
        }

        if ($this->stageConfig->get(DeployInterface::VAR_SKIP_SCD)
            || !$this->environment->isDeployStaticContent()
        ) {
            return;
        }

        if ($this->stageConfig->get(DeployInterface::VAR_CLEAN_STATIC_FILES)) {
            $magentoRoot = $this->directoryList->getMagentoRoot();
            $this->logger->info('Clearing pub/static');
            $this->file->backgroundClearDirectory($magentoRoot . '/pub/static');
            $this->logger->info('Clearing var/view_preprocessed');
            $this->file->backgroundClearDirectory($magentoRoot . '/var/view_preprocessed');
        }

        $this->logger->info('Generating fresh static content');
        $this->process->execute();
    }
}
