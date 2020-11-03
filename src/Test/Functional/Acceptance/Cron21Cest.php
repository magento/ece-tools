<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Test\Functional\Acceptance;

/**
 * @group php71
 */
class Cron21Cest extends CronCest
{
    /**
     * @return array
     */
    protected function cronDataProvider(): array
    {
        return [
            [
                'version' => '2.1.18',
                'variables' => [
                    'MAGENTO_CLOUD_VARIABLES' => [
                        'ADMIN_EMAIL' => 'admin@example.com',
                        'ADMIN_LOCALE' => 'ar_KW'
                    ],
                ],
            ],
        ];
    }
}
