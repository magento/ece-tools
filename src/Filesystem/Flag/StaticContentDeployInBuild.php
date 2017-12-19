<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Filesystem\Flag;

/**
 * @inheritdoc
 */
class StaticContentDeployInBuild implements FlagInterface
{
    const KEY = 'scd_in_build';

    /**
     * @inheritdoc
     */
    public function getPath(): string
    {
        return '.static_content_deploy';
    }

    /**
     * @inheritdoc
     */
    public function getKey(): string
    {
        return self::KEY;
    }
}
