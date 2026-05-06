<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Test\Functional\Acceptance;

/**
 * Checks MariaDB version validation on PHP 8.4
 *
 * @group php84
 */
class MariaDbVersion84Cest extends MariaDbVersionCest
{
    /**
     * Provides MariaDB compatibility scenarios for PHP 8.4 templates.
     *
     * @return array
     */
    protected function mariaDbVersionDataProvider(): array
    {
        return [
            '2.4.8_supported_11_8' => [
                'magentoCloudTemplate' => '2.4.8',
                'mariaDbVersion' => '11.8',
                'expectedSuccess' => true,
            ],
            '2.4.8_supported_11_4' => [
                'magentoCloudTemplate' => '2.4.8',
                'mariaDbVersion' => '11.4',
                'expectedSuccess' => true,
            ],
        ];
    }
}
