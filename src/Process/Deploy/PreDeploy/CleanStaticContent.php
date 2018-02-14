<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Process\Deploy\PreDeploy;

use Magento\MagentoCloud\Config\Environment;
use Magento\MagentoCloud\Filesystem\Driver\File;
use Magento\MagentoCloud\Process\ProcessInterface;
use Magento\MagentoCloud\Filesystem\Flag\Manager as FlagManager;
use Magento\MagentoCloud\Filesystem\DirectoryList;
use Psr\Log\LoggerInterface;

/**
 * Cleans static content.
 */
class CleanStaticContent implements ProcessInterface
{
    /**
     * @var Environment
     */
    private $env;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var FlagManager
     */
    private $flagManager;

    /**
     * @var File
     */
    private $file;

    /**
     * @var DirectoryList
     */
    private $directoryList;

    /**
     * @param LoggerInterface $logger
     * @param Environment $env
     * @param File $file
     * @param DirectoryList $directoryList
     * @param FlagManager $flagManager
     */
    public function __construct(
        LoggerInterface $logger,
        Environment $env,
        File $file,
        DirectoryList $directoryList,
        FlagManager $flagManager
    ) {
        $this->logger = $logger;
        $this->env = $env;
        $this->file = $file;
        $this->directoryList = $directoryList;
        $this->flagManager = $flagManager;
    }

    /**
     * Clean static files if static content deploy was performed during build phase.
     *
     * {@inheritdoc}
     */
    public function execute()
    {
        if (!$this->flagManager->exists(FlagManager::FLAG_STATIC_CONTENT_DEPLOY_IN_BUILD)) {
            return;
        }

        $this->logger->info('Static content deployment was performed during build hook, cleaning old content.');
        $magentoRoot = $this->directoryList->getMagentoRoot();
        $this->logger->info('Clearing pub/static');
        $this->file->backgroundClearDirectory($magentoRoot . '/pub/static');
    }
}
