<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Util;

use Magento\MagentoCloud\Filesystem\DirectoryList;
use Magento\MagentoCloud\Filesystem\Driver\File;
use Magento\MagentoCloud\Shell\ShellInterface;
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
     * @var ShellInterface
     */
    private $shell;

    /**
     * @var File
     */
    private $file;

    /**
     * @param LoggerInterface $logger
     * @param ShellInterface $shell
     * @param DirectoryList $directoryList
     * @param File $file
     */
    public function __construct(
        LoggerInterface $logger,
        ShellInterface $shell,
        DirectoryList $directoryList,
        File $file
    ) {
        $this->logger = $logger;
        $this->shell = $shell;
        $this->directoryList = $directoryList;
        $this->file = $file;
    }

    /**
     * @param string $dir The directory to copy. Pass in its normal location relative to Magento root with no prepending
     *                    or trailing slashes
     */
    public function copy($dir)
    {
        $fullPathDir = $this->directoryList->getMagentoRoot() . $dir;
        if (!$this->file->isExists($fullPathDir)) {
            $this->file->createDirectory($fullPathDir);
            $this->logger->info(sprintf('Created directory: %s', $dir));
        }
        $this->shell->execute(sprintf('/bin/bash -c "shopt -s dotglob; cp -R ./init/%s/* %s/ || true"', $dir, $dir));
        $this->logger->info(sprintf('Copied directory: %s', $dir));
    }
}
