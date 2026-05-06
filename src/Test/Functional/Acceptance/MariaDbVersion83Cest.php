<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Test\Functional\Acceptance;

/**
 * Checks MariaDB version validation on PHP 8.3
 *
 * @group php83
 */
class MariaDbVersion83Cest extends MariaDbVersionCest
{
    /**
     * Provides MariaDB compatibility scenarios for PHP 8.3 templates.
     *
     * @return array
     */
    protected function mariaDbVersionDataProvider(): array
    {
        return [
            '2.4.7_supported_11_8' => [
                'magentoCloudTemplate' => '2.4.7',
                'mariaDbVersion' => '11.8',
                'expectedSuccess' => true,
            ],
            '2.4.7_supported_10_11' => [
                'magentoCloudTemplate' => '2.4.7',
                'mariaDbVersion' => '10.11',
                'expectedSuccess' => true,
            ]
        ];
    }
}
