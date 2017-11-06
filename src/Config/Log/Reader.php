<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Config\Log;

use Magento\MagentoCloud\Filesystem\Reader\ReaderInterface;
use Magento\MagentoCloud\Filesystem\FileList;
use Magento\MagentoCloud\Filesystem\Driver\File;
use Symfony\Component\Yaml\Yaml;
use Symfony\Component\Yaml\Exception\ParseException;

/**
 * @inheritdoc
 */
class Reader implements ReaderInterface
{
    /**
     * @var FileList
     */
    private $fileList;

    /**
     * @var File
     */
    private $file;

    /**
     * @param FileList $fileList
     * @param File $file
     */
    public function __construct(FileList $fileList, File $file)
    {
        $this->fileList = $fileList;
        $this->file = $file;
    }

    /**
     * @return array
     * @throws ParseException
     */
    public function read(): array
    {
        $path = $this->fileList->getLogConfig();

        return !$this->file->isExists($path) ? [] : (array) Yaml::parse($this->file->fileGetContents($path));
    }
}
