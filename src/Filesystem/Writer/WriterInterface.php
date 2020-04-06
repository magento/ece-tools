<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Filesystem\Writer;

use Magento\MagentoCloud\Filesystem\FilesystemException;

/**
 * Write content of file.
 */
interface WriterInterface
{
    /**
     * Writes given configuration to file.
     *
     * @param array $config
     * @return void
     * @throws FilesystemException
     */
    public function create(array $config);

    /**
     * Recursively updates existence configuration.
     *
     * @param array $config
     * @return void
     * @throws FilesystemException
     */
    public function update(array $config);
}
