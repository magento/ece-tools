<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Config\Stage;

use Magento\MagentoCloud\Config\StageConfigInterface;

/**
 * @inheritdoc
 *
 * @api
 */
interface PostDeployInterface extends StageConfigInterface
{
    const VAR_WARM_UP_PAGES = 'WARM_UP_PAGES';
    const VAR_WARM_UP_CONCURRENCY = 'WARM_UP_CONCURRENCY';
    const VAR_TTFB_TESTED_PAGES = 'TTFB_TESTED_PAGES';
}
