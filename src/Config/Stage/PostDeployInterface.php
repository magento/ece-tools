<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Config\Stage;

use Magento\MagentoCloud\Config\StageConfigInterface;

/**
 * @inheritdoc
 */
interface PostDeployInterface extends StageConfigInterface
{
    const VAR_WARM_UP_PAGES = 'WARM_UP_PAGES';
    const VAR_TTFB_TESTED_PAGES = 'TTFB_TESTED_PAGES';
}
