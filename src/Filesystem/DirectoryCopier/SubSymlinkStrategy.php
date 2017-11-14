<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Filesystem\DirectoryCopier;

use Magento\MagentoCloud\Filesystem\Driver\File;
use Magento\MagentoCloud\Filesystem\FileSystemException;
use Psr\Log\LoggerInterface;

/**
 * Creates symlink from directory folders and files to another directory.
 */
class SubSymlinkStrategy implements StrategyInterface
{
    /**
     * @var File
     */
    private $file;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param File $file
     * @param LoggerInterface $logger
     */
    public function __construct(
        File $file,
        LoggerInterface $logger
    ) {
        $this->file = $file;
        $this->logger = $logger;
    }

    /**
     * Creates symlink from directory folders and files to another directory.
     *
     * @inheritdoc
     */
    public function copy(string $fromDirectory, string $toDirectory): bool
    {
        $fromDirectory = $this->file->getRealPath($fromDirectory);
        $toDirectory = $this->file->getRealPath($toDirectory);

        if (!$this->file->isExists($fromDirectory)) {
            throw new FileSystemException(
                sprintf('Can\'t copy directory %s. Directory does not exist.', $fromDirectory)
            );
        }

        $directoryIterator = new \DirectoryIterator($fromDirectory);
        foreach ($directoryIterator as $fileInfo) {
            if ($fileInfo->isDot()) {
                continue;
            }

            $fromDir = $fromDirectory . '/' . $fileInfo->getFilename();
            $toDir = $toDirectory . '/' . $fileInfo->getFilename();

            $this->file->symlink($fromDir, $toDir);
        }

        return true;
    }
}
