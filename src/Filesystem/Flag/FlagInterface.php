<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Filesystem\Flag;

/**
 * Interface for flags.
 */
interface FlagInterface
{
    /**
     * Returns flag path relative to magento root.
     *
     * @return string
     */
    public function getPath(): string;
}
