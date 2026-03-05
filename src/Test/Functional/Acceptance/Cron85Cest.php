<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Test\Functional\Acceptance;

/**
 * @group php85
 */
class Cron85Cest extends CronCest
{
    /**
     * Data provider for Magento Cloud versions.
     *
     * @return array
     */
    protected function cronDataProvider(): array
    {
        return [
            [
                'version' => '2.4.9-alpha-opensearch3.0',
                'variables' => [
                    'MAGENTO_CLOUD_VARIABLES' => [
                        'ADMIN_EMAIL' => 'admin@example.com',
                        'ADMIN_LOCALE' => 'fr_FR'
                    ],
                ],
            ],
        ];
    }
}
