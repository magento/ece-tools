<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Filesystem\FlagFile\Flag;

use Magento\MagentoCloud\Filesystem\FlagFile\FlagInterface;

/**
 * @inheritdoc
 */
class Regenerate implements FlagInterface
{
    const KEY = 'regenerate';

    /**
     * @inheritdoc
     */
    public function getPath(): string
    {
        return 'var/.regenerate';
    }

    /**
     * @inheritdoc
     */
    public function getKey(): string
    {
        return self::KEY;
    }
}
