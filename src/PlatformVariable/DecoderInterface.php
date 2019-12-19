<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\PlatformVariable;

/**
 * Decodes data from Cloud format.
 */
interface DecoderInterface
{
    /**
     * Decodes string from Cloud format
     *
     * @param string $encodedString
     * @return mixed
     */
    public function decode(string $encodedString);
}
