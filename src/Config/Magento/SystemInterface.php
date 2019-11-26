<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Config\Magento;

/**
 * Retrieves values from magento configuration.
 *
 * @api
 */
interface SystemInterface
{
    /**
     * Retrieves values from magento configuration by option name.
     *
     * @param string $key
     * @return string|null
     */
    public function get(string $key): ?string;
}
