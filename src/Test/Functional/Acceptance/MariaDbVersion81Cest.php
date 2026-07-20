<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Test\Functional\Acceptance;

/**
 * Checks MariaDB version validation on PHP 8.1
 *
 * @group php81
 */
class MariaDbVersion81Cest extends MariaDbVersionCest
{
    /**
     * Provides MariaDB compatibility scenarios for PHP 8.1 templates.
     *
     * @return array
     */
    protected function mariaDbVersionDataProvider(): array
    {
        return [
            '2.4.5_supported_10_11' => [
                'magentoCloudTemplate' => '2.4.5',
                'mariaDbVersion' => '10.11',
                'expectedSuccess' => true,
            ]
        ];
    }
}
