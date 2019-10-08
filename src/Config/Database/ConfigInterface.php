<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Config\Database;

/**
 * Interface for final database configuration.
 */
interface ConfigInterface
{
    /**
     * Returns database configuration.
     *
     * @return array
     */
    public function get(): array;
}
