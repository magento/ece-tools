<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Filesystem\Writer;

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
     */
    public function create(array $config);

    /**
     * Updates existence configuration.
     *
     * @param array $config
     * @return void
     */
    public function update(array $config);
}
