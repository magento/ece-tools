<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Filesystem\DirectoryCopier;

use Magento\MagentoCloud\Filesystem\Driver\File;
use Magento\MagentoCloud\Filesystem\FileSystemException;

/**
 * Creates symlink from one folder to another.
 */
class SymlinkStrategy implements StrategyInterface
{
    /**
     * @var File
     */
    private $file;

    /**
     * @param File $file
     */
    public function __construct(
        File $file
    ) {
        $this->file = $file;
    }

    /**
     * Creates symlink from one folder to another. Remove or unlink directory if it exists previously.
     *
     * @inheritdoc
     */
    public function copy(string $fromDirectory, string $toDirectory): bool
    {
        if (!$this->file->isExists($fromDirectory)) {
            throw new FileSystemException(
                sprintf('Can\'t copy directory %s. Directory does not exist.', $fromDirectory)
            );
        }

        if ($this->file->isExists($toDirectory)) {
            if ($this->file->isLink($toDirectory)) {
                $this->file->unLink($toDirectory);
            } else {
                $this->file->deleteDirectory($toDirectory);
            }
        }

        return $this->file->symlink($fromDirectory, $toDirectory);
    }
}
