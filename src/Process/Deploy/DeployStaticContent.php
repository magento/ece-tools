<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Process\Deploy;

use Magento\MagentoCloud\Config\Environment;
use Magento\MagentoCloud\Filesystem\DirectoryList;
use Magento\MagentoCloud\Filesystem\Driver\File;
use Magento\MagentoCloud\Filesystem\FlagFile\StaticContentDeployFlag;
use Magento\MagentoCloud\Filesystem\FlagFile\StaticContentDeployPendingFlag;
use Magento\MagentoCloud\Filesystem\FlagFilePool;
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
     * @var FlagFilePool
     */
    private $flagFilePool;

    /**
     * DeployStaticContent constructor.
     * @param ProcessInterface $process
     * @param Environment $environment
     * @param LoggerInterface $logger
     * @param File $file
     * @param DirectoryList $directoryList
     * @param RemoteDiskIdentifier $remoteDiskIdentifier
     * @param FlagFilePool $flagFilePool
     */
    public function __construct(
        ProcessInterface $process,
        Environment $environment,
        LoggerInterface $logger,
        File $file,
        DirectoryList $directoryList,
        RemoteDiskIdentifier $remoteDiskIdentifier,
        FlagFilePool $flagFilePool
    ) {
        $this->process = $process;
        $this->environment = $environment;
        $this->logger = $logger;
        $this->file = $file;
        $this->directoryList = $directoryList;
        $this->remoteDiskIdentifier = $remoteDiskIdentifier;
        $this->flagFilePool = $flagFilePool;
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
        $scdInBuildFlag = $this->flagFilePool->getFlag(StaticContentDeployFlag::KEY);
        $scdPendingFlag = $this->flagFilePool->getFlag(StaticContentDeployPendingFlag::KEY);
        $scdPendingFlag->delete();
        if ($this->remoteDiskIdentifier->isOnLocalDisk('pub/static') && !$scdInBuildFlag->exists()) {
            $scdPendingFlag->set();
            $this->logger->info('Postpone static content deployment until prestart');
            return;
        }

        $applicationMode = $this->environment->getApplicationMode();
        $this->logger->info('Application mode is ' . $applicationMode);

        if ($applicationMode !== Environment::MAGENTO_PRODUCTION_MODE) {
            return;
        }

        /* Workaround for MAGETWO-58594: disable redis cache before running static deploy, re-enable after */
        if (!$this->environment->isDeployStaticContent()) {
            return;
        }

        // Clear old static content if necessary
        if ($this->environment->doCleanStaticFiles()) {
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
