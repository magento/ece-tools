<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Config\Build;

use Magento\MagentoCloud\Filesystem\DirectoryList;
use Magento\MagentoCloud\Filesystem\Driver\File;
use Magento\MagentoCloud\Filesystem\Reader\ReaderInterface;

/**
 * @inheritdoc
 */
class Reader implements ReaderInterface
{
    /**
     * @var File
     */
    private $file;

    /**
     * @var DirectoryList
     */
    private $directoryList;

    /**
     * @param File $file
     * @param DirectoryList $directoryList
     */
    public function __construct(File $file, DirectoryList $directoryList)
    {
        $this->file = $file;
        $this->directoryList = $directoryList;
    }

    /**
     * @inheritdoc
     */
    public function read(): array
    {
        $fileName = $this->getPath();

        return $this->file->isExists($fileName) ? $this->file->parseIni($fileName) : [];
    }

    /**
     * @return string
     */
    public function getPath(): string
    {
        return $this->directoryList->getMagentoRoot() . '/build_options.ini';
    }
}
