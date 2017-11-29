<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Process\Deploy\PreDeploy;

use Magento\MagentoCloud\Config\Environment;
use Magento\MagentoCloud\Filesystem\Driver\File;
use Magento\MagentoCloud\Process\ProcessInterface;
use Magento\MagentoCloud\Filesystem\FlagFilePool;
use Magento\MagentoCloud\Filesystem\DirectoryList;
use Psr\Log\LoggerInterface;

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
     * @var FlagFilePool
     */
    private $flagFilePool;

    /**
     * @param LoggerInterface $logger
     * @param Environment $env
     * @param File $file
     * @param DirectoryList $directoryList
     * @param FlagFilePool $flagFilePool
     */
    public function __construct(
        LoggerInterface $logger,
        Environment $env,
        File $file,
        DirectoryList $directoryList,
        FlagFilePool $flagFilePool
    ) {
        $this->logger = $logger;
        $this->env = $env;
        $this->file = $file;
        $this->directoryList = $directoryList;
        $this->flagFilePool = $flagFilePool;
    }

    /**
     * Clean static files if static content deploy was performed during build phase.
     *
     * {@inheritdoc}
     */
    public function execute()
    {
        if (!$this->flagFilePool->getFlag('scd_in_build')->exists()) {
            return;
        }

        $this->logger->info('Static content deployment was performed during build hook, cleaning old content.');
        $magentoRoot = $this->directoryList->getMagentoRoot();
        $this->logger->info('Clearing pub/static');
        $this->file->backgroundClearDirectory($magentoRoot . '/pub/static');
    }
}
