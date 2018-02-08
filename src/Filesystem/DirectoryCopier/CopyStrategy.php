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
 * @inheritdoc
 */
class CopyStrategy implements StrategyInterface
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
     * @inheritdoc
     */
    public function copy(string $fromDirectory, string $toDirectory): bool
    {
        if (!$this->file->isExists($fromDirectory)) {
            throw new FileSystemException(
                sprintf('Can\'t copy directory %s. Directory does not exist.', $fromDirectory)
            );
        }

        if ($this->file->isEmptyDirectory($fromDirectory)) {
            $this->logger->info(sprintf("%s is empty. Nothing to restore", $fromDirectory));

            return false;
        }

        if ($this->file->isLink($toDirectory)) {
            $this->file->unLink($toDirectory);
        }

        if (!$this->file->isExists($toDirectory)) {
            $this->file->createDirectory($toDirectory);
        }

        $this->file->copyDirectory($fromDirectory, $toDirectory);

        return true;
    }
}
