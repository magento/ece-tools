<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Util;

use Magento\MagentoCloud\Filesystem\DirectoryList;
use Magento\MagentoCloud\Filesystem\Driver\File;
use Magento\MagentoCloud\Filesystem\FileSystemException;
use Psr\Log\LoggerInterface;

class StaticContentSymlink
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
     * Creates symlinks for static content pub/static => init/pub/static
     *
     * @return void
     */
    public function create()
    {
        $magentoRoot = $this->directoryList->getMagentoRoot();

        // Symlink pub/static/* to init/pub/static/*
        $staticContentLocation = $this->file->getRealPath($magentoRoot . '/pub/static');
        $buildDir = $this->file->getRealPath($magentoRoot . '/init/pub/static');
        if ($this->file->isExists($buildDir)) {
            $dir = new \DirectoryIterator($buildDir);
            foreach ($dir as $fileInfo) {
                if ($fileInfo->isDot()) {
                    continue;
                }

                $fromDir = $buildDir . '/' . $fileInfo->getFilename();
                $toDir = $staticContentLocation . '/' . $fileInfo->getFilename();

                try {
                    if ($this->file->symlink($fromDir, $toDir)) {
                        $this->logger->info(sprintf('Create symlink %s => %s', $toDir, $fromDir));
                    }
                } catch (FileSystemException $e) {
                    $this->logger->error($e->getMessage());
                }
            }
        }
    }
}
