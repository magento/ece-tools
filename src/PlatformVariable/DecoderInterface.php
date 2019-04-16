<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
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
