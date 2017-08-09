<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Config\Build;

use Magento\MagentoCloud\Environment;
use Magento\MagentoCloud\Filesystem\Reader\ReaderInterface;

class Reader implements ReaderInterface
{
    /**
     * @inheritdoc
     */
    public function read(): array
    {
        $fileName = Environment::MAGENTO_ROOT . '/build_options.ini';

        return file_exists($fileName)
            ? parse_ini_file(Environment::MAGENTO_ROOT . '/build_options.ini')
            : [];
    }
}
