<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Config\Schema;

/**
 * Data formatter
 */
interface FormatterInterface
{
    /**
     * Format data
     *
     * @param array $data
     * @return string
     */
    public function format(array $data): string;
}
