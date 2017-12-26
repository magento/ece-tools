<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Filesystem\Flag;

/**
 * Used for postponing static content deployment until prestart phase.
 *
 * {@inheritdoc}
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
}
