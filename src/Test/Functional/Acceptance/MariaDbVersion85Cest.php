<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Test\Functional\Acceptance;

/**
 * Checks MariaDB version validation on PHP 8.5
 *
 * @group php85
 */
class MariaDbVersion85Cest extends MariaDbVersionCest
{
    /**
     * Provides MariaDB compatibility scenarios for PHP 8.5 templates.
     *
     * @return array
     */
    protected function mariaDbVersionDataProvider(): array
    {
        return [
            '2.4.9_supported_11.4' => [
                'magentoCloudTemplate' => '2.4.9-beta',
                'mariaDbVersion' => '11.4',
                'expectedSuccess' => true,
            ],
            '2.4.9_supported_11.8' => [
                'magentoCloudTemplate' => '2.4.9-beta',
                'mariaDbVersion' => '11.8',
                'expectedSuccess' => true,
            ],
            '2.4.9_supported_12.2' => [
                'magentoCloudTemplate' => '2.4.9-beta',
                'mariaDbVersion' => '12.2',
                'expectedSuccess' => true,
            ],
            '2.4.9_supported_12.3' => [
                'magentoCloudTemplate' => '2.4.9-beta',
                'mariaDbVersion' => '12.3-rc',
                'expectedSuccess' => true,
            ],
        ];
    }
}
