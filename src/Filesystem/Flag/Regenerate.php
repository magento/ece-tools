<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Filesystem\Flag;

/**
 * This flag is creating by magento for cleaning up generated/code, generated/metadata and var/cache directories
 * for subsequent regeneration of this content.
 *
 * {@inheritdoc}
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
}
