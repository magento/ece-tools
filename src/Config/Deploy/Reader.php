<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Config\Deploy;

use Magento\MagentoCloud\Filesystem\DirectoryList;
use Magento\MagentoCloud\Filesystem\Driver\File;
use Magento\MagentoCloud\Filesystem\Reader\ReaderInterface;

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
     * @return array
     */
    public function read(): array
    {
        $configPath = $this->getPath();
        if (!$this->file->isExists($configPath)) {
            return [];
        }

        return include $configPath;
    }

    /**
     * @return string
     */
    public function getPath(): string
    {
        return $this->directoryList->getMagentoRoot() . '/app/etc/env.php';
    }
}
