<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Config\Build;

use Magento\MagentoCloud\Filesystem\FileList;
use Magento\MagentoCloud\Filesystem\Driver\File;
use Magento\MagentoCloud\Filesystem\Reader\ReaderInterface;

/**
 * {@inheritdoc}
 *
 * @deprecated
 */
class Reader implements ReaderInterface
{
    /**
     * @var File
     */
    private $file;

    /**
     * @var FileList
     */
    private $fileList;

    /**
     * Cached configuration
     *
     * @var array|null
     */
    private $config;

    /**
     * @param File $file
     * @param FileList $fileList
     */
    public function __construct(File $file, FileList $fileList)
    {
        $this->file = $file;
        $this->fileList = $fileList;
    }

    /**
     * {@inheritdoc}
     *
     * @deprecated
     */
    public function read(): array
    {
        if ($this->config === null) {
            $fileName = $this->fileList->getBuildConfig();

            $this->config = $this->file->isExists($fileName) ? $this->file->parseIni($fileName) : [];
        }

        return $this->config;
    }
}
