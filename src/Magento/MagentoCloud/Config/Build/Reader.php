<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Config\Build;

use Magento\MagentoCloud\Filesystem\Driver\File;
use Magento\MagentoCloud\Filesystem\Reader\ReaderInterface;

class Reader implements ReaderInterface
{
    /**
     * @var File
     */
    private $file;

    /**
     * @param File $file
     */
    public function __construct(File $file)
    {
        $this->file = $file;
    }

    /**
     * @inheritdoc
     */
    public function read(): array
    {
        $fileName = MAGENTO_ROOT . '/build_options.ini';

        return $this->file->isExists($fileName) ? parse_ini_file($fileName) : [];
    }
}
