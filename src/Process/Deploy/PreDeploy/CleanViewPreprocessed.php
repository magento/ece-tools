<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Process\Deploy\PreDeploy;

use Magento\MagentoCloud\Process\ProcessInterface;
use Magento\MagentoCloud\Filesystem\Driver\File;
use Magento\MagentoCloud\Filesystem\DirectoryList;
use Psr\Log\LoggerInterface;
use Magento\MagentoCloud\Config\Stage\DeployInterface;

class CleanViewPreprocessed implements ProcessInterface
{
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
     * @var DeployInterface
     */
    private $stageConfig;

    /**
     * @param LoggerInterface $logger
     * @param File $file
     * @param DirectoryList $directoryList
     * @param DeployInterface $stageConfig
     */
    public function __construct(
        LoggerInterface $logger,
        File $file,
        DirectoryList $directoryList,
        DeployInterface $stageConfig
    ) {
        $this->logger = $logger;
        $this->file = $file;
        $this->directoryList = $directoryList;
        $this->stageConfig = $stageConfig;
    }

    /**
     * Clean the dir var/view_preprocessed
     *
     * {@inheritdoc}
     */
    public function execute()
    {
        if (!$this->stageConfig->get(DeployInterface::VAR_SKIP_COPYING_VIEW_PREPROCESSED_DIR)) {
            return;
        }

        $this->logger->info('Skip copying directory ./var/view_preprocessed.');
        $magentoRoot = $this->directoryList->getMagentoRoot();
        $this->logger->info('Clearing ./var/view_preprocessed');
        $this->file->backgroundClearDirectory($magentoRoot . '/var/view_preprocessed');
    }
}
