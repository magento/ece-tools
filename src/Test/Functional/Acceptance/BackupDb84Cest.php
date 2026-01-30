<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Test\Functional\Acceptance;

/**
 * Checks database backup functionality
 * @group php84
 */
class BackupDb84Cest extends BackupDbCest
{
    /**
     * Data provider for Magento Cloud versions.
     *
     * @return array
     */
    protected function dataProviderMagentoCloudVersions(): array
    {
        return [
            ['version' => '2.4.8'],
        ];
    }
}
