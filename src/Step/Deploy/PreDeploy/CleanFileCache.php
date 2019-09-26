<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Step\Deploy\PreDeploy;

use Magento\MagentoCloud\Filesystem\DirectoryList;
use Magento\MagentoCloud\Filesystem\Driver\File;
use Magento\MagentoCloud\Step\StepInterface;
use Psr\Log\LoggerInterface;

/**
 * Cleans file caches.
 */
class CleanFileCache implements StepInterface
{
    /**
     * @var DirectoryList
     */
    private $directoryList;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var File
     */
    private $file;

    /**
     * @param LoggerInterface $logger
     * @param DirectoryList $directoryList
     * @param File $file
     */
    public function __construct(
        LoggerInterface $logger,
        DirectoryList $directoryList,
        File $file
    ) {
        $this->logger = $logger;
        $this->directoryList = $directoryList;
        $this->file = $file;
    }

    /**
     * Clears var/cache directory if such directory exists
     *
     * @return void
     */
    public function execute()
    {
        $fileCacheDir = $this->directoryList->getMagentoRoot() . '/var/cache';

        if ($this->file->isExists($fileCacheDir)) {
            $this->logger->info('Clearing var/cache directory');
            $this->file->deleteDirectory($fileCacheDir);
        }
    }
}
