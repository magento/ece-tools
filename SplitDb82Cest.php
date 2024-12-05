<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Test\Functional\Acceptance;

/**
 * Checks split database functionality
 *
 * @group php82
 */
class SplitDb82Cest extends SplitDbCest
{
    /**
     * @return array
     */
    protected function dataProviderMagentoCloudVersions(): array
    {
        return [
            ['version' => '2.4.6'],
        ];
    }
}
