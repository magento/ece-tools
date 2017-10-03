<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Filesystem\Reader;

/**
 * Read content of file.
 */
interface ReaderInterface
{
    /**
     * @return array
     */
    public function read(): array;

    /**
     * @return string
     */
    public function getPath(): string;
}
