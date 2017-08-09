<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Filesystem\Reader;

interface ReaderInterface
{
    /**
     * @return array
     */
    public function read(): array;
}
