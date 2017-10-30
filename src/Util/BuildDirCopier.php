<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Util;

use Magento\MagentoCloud\Filesystem\DirectoryList;
use Magento\MagentoCloud\Filesystem\Driver\File;
use Psr\Log\LoggerInterface;

class BuildDirCopier
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
     * @param string $dir The directory to copy. Pass in its normal location relative to Magento root with no prepending
     *                    or trailing slashes
     */
    public function copy($dir)
    {
        $magentoRoot = $this->directoryList->getMagentoRoot();
        $originalDir = $magentoRoot . '/' . $dir;
        $initDir = $magentoRoot . '/init/' . $dir;

        if (!$this->file->isExists($initDir)) {
            $this->logger->notice(sprintf('Can\'t copy directory %s. Directory does not exist.', $magentoRoot));
            return;
        }

        if (!$this->file->isExists($originalDir)) {
            $this->file->createDirectory($originalDir);
            $this->logger->info(sprintf('Created directory: %s', $dir));
        }

        $this->file->copyDirectory($initDir, $originalDir);
        $this->logger->info(sprintf('Copied directory: %s', $dir));
    }
}
