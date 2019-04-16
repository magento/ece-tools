<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\CloudVariable;

/**
 * Encodes data into Cloud format.
 */
class Encoder implements EncoderInterface
{
    /**
     * @inheritdoc
     */
    public function encode($data): string
    {
        return base64_encode(json_encode($data));
    }
}
