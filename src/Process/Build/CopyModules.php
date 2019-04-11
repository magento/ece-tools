<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Process\Build;

use Magento\MagentoCloud\Filesystem\DirectoryList;
use Magento\MagentoCloud\Filesystem\Driver\File;
use Magento\MagentoCloud\Process\ProcessInterface;
use Psr\Log\LoggerInterface;

/**
 * @inheritdoc
 */
class CopyModules implements ProcessInterface
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
     * @param LoggerInterface $logger
     * @param File $file
     * @param DirectoryList $directoryList
     */
    public function __construct(
        LoggerInterface $logger,
        File $file,
        DirectoryList $directoryList
    ) {
        $this->logger = $logger;
        $this->file = $file;
        $this->directoryList = $directoryList;
    }

    /**
     * @inheritdoc
     */
    public function execute()
    {
        $magentoRoot = $this->directoryList->getMagentoRoot();
        $magentoModulesDirectory = $magentoRoot . '/app/code/Magento';
        $eceToolRoot = $this->directoryList->getRoot();

        $this->logger->info('Copying ece-tool modules');

        if (!$this->file->isDirectory($magentoModulesDirectory)) {
            $this->file->createDirectory($magentoModulesDirectory);
        }

        $this->file->copyDirectory($eceToolRoot . '/modules/Magento', $magentoModulesDirectory);
    }
}
