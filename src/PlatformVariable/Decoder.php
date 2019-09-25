<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\PlatformVariable;

/**
 * @inheritdoc
 */
class Decoder implements DecoderInterface
{
    /**
     * @inheritdoc
     */
    public function decode(string $encodedString)
    {
        return json_decode(base64_decode($encodedString), true);
    }
}
