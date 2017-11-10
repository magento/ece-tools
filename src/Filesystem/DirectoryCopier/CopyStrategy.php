<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Filesystem\DirectoryCopier;

use Magento\MagentoCloud\Filesystem\Driver\File;
use Magento\MagentoCloud\Filesystem\FileSystemException;

class CopyStrategy implements StrategyInterface
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
     * @inheritdoc
     */
    public function copy(string $fromDirectory, string $toDirectory): bool
    {
        if (!$this->file->isExists($fromDirectory)) {
            throw new FileSystemException(
                sprintf('Can\'t copy directory %s. Directory does not exist.', $fromDirectory)
            );
        }

        if (!$this->file->isExists($toDirectory)) {
            $this->file->createDirectory($toDirectory);
        }

        $this->file->copyDirectory($fromDirectory, $toDirectory);

        return true;
    }
}
