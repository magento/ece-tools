<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\CloudVariable;

/**
 * Encodes data into Cloud format.
 */
interface EncoderInterface
{
    /**
     * Encodes $data into Cloud format
     *
     * @param $data
     * @return string
     */
    public function encode($data): string;
}
