<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Filesystem\Reader;

use Magento\MagentoCloud\Filesystem\FileSystemException;

/**
 * Read content of file.
 */
interface ReaderInterface
{
    /**
     * @return array
     * @throws FileSystemException
     */
    public function read(): array;
}
