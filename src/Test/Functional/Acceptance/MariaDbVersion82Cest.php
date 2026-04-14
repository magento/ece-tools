<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Test\Functional\Acceptance;

/**
 * Checks MariaDB version validation on PHP 8.2
 *
 * @group php82
 */
class MariaDbVersion82Cest extends MariaDbVersionCest
{
    /**
     * Provides MariaDB compatibility scenarios for PHP 8.2 templates.
     *
     * @return array
     */
    protected function mariaDbVersionDataProvider(): array
    {
        return [
            '2.4.6_supported_10_11' => [
                'magentoCloudTemplate' => '2.4.6',
                'mariaDbVersion' => '10.11',
                'expectedSuccess' => true,
            ]
        ];
    }
}
