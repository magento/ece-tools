<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Util;

/**
 * Encodes and decodes data in Cloud format.
 */
class CloudVariableEncoder
{
    /**
     * Decodes string from Cloud format
     *
     * @param string $encodedString
     * @return mixed
     */
    public function decode(string $encodedString)
    {
        return json_decode(base64_decode($encodedString), true);
    }

    /**
     * Encodes $data into Cloud format
     *
     * @param $data
     * @return string
     */
    public function encode($data): string
    {
        return base64_encode(json_encode($data));
    }
}
