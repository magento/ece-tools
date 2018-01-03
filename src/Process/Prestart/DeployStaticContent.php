<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Process\Prestart;

use Magento\MagentoCloud\Config\Environment;
use Magento\MagentoCloud\Config\Stage\DeployInterface;
use Magento\MagentoCloud\Filesystem\DirectoryList;
use Magento\MagentoCloud\Filesystem\Driver\File;
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
     * @param FlagFilePool $flagFilePool
     * @param DeployInterface $stageConfig
     */
    public function __construct(
        ProcessInterface $process,
        Environment $environment,
        LoggerInterface $logger,
        File $file,
        DirectoryList $directoryList,
        RemoteDiskIdentifier $remoteDiskIdentifier,
        FlagFilePool $flagFilePool,
        DeployInterface $stageConfig
    ) {
        $this->process = $process;
        $this->environment = $environment;
        $this->logger = $logger;
        $this->file = $file;
        $this->directoryList = $directoryList;
        $this->remoteDiskIdentifier = $remoteDiskIdentifier;
        $this->flagFilePool = $flagFilePool;
        $this->stageConfig = $stageConfig;
    }

    /**
     * This function deploys the static content to local storage during the prestart hook
     *
     * {@inheritdoc}
     */
    public function execute()
    {
        if ($this->remoteDiskIdentifier->isOnLocalDisk('pub/static')
            && $this->flagFilePool->getFlag(StaticContentDeployPendingFlag::KEY)->exists()
        ) {
            if (Environment::MAGENTO_PRODUCTION_MODE !== $this->environment->getApplicationMode()) {
                return;
            }

            if ($this->stageConfig->get(DeployInterface::VAR_SKIP_SCD) ||
                !$this->environment->isDeployStaticContent()
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
}
