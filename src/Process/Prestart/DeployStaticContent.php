<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Process\Prestart;

use Magento\MagentoCloud\Config\Environment;
use Magento\MagentoCloud\Filesystem\DirectoryList;
use Magento\MagentoCloud\Filesystem\Driver\File;
use Magento\MagentoCloud\Filesystem\Flag\StaticContentDeployPending;
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
     * DeployStaticContent constructor.
     * @param ProcessInterface $process
     * @param Environment $environment
     * @param LoggerInterface $logger
     * @param File $file
     * @param DirectoryList $directoryList
     * @param RemoteDiskIdentifier $remoteDiskIdentifier
     * @param FlagManager $flagManager
     */
    public function __construct(
        ProcessInterface $process,
        Environment $environment,
        LoggerInterface $logger,
        File $file,
        DirectoryList $directoryList,
        RemoteDiskIdentifier $remoteDiskIdentifier,
        FlagManager $flagManager
    ) {
        $this->process = $process;
        $this->environment = $environment;
        $this->logger = $logger;
        $this->file = $file;
        $this->directoryList = $directoryList;
        $this->remoteDiskIdentifier = $remoteDiskIdentifier;
        $this->flagManager = $flagManager;
    }

    /**
     * This function deploys the static content to local storage during the prestart hook
     *
     * {@inheritdoc}
     */
    public function execute()
    {
        if ($this->remoteDiskIdentifier->isOnLocalDisk('pub/static')
            && $this->flagManager->exists(StaticContentDeployPending::KEY)
        ) {
            if (Environment::MAGENTO_PRODUCTION_MODE !== $this->environment->getApplicationMode()) {
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
}
