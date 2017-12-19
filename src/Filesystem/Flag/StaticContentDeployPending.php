<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Filesystem\Flag;

/**
 * @inheritdoc
 */
class StaticContentDeployPending implements FlagInterface
{
    const KEY = 'scd_pending';

    /**
     * @inheritdoc
     */
    public function getPath(): string
    {
        return 'var/.static_content_deploy_pending';
    }

    /**
     * @inheritdoc
     */
    public function getKey(): string
    {
        return self::KEY;
    }
}
