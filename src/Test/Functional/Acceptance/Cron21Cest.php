<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Test\Functional\Acceptance;

/**
 * @group php70
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
                'version' => '2.1.4',
                'variables' => [
                    'MAGENTO_CLOUD_VARIABLES' => [
                        'ADMIN_EMAIL' => 'admin@example.com',
                        'ADMIN_LOCALE' => 'fr_FR'
                    ],
                ],
            ],
            [
                'version' => '2.1.6',
                'variables' => [
                    'MAGENTO_CLOUD_VARIABLES' => [
                        'ADMIN_EMAIL' => 'admin@example.com',
                        'ADMIN_LOCALE' => 'ar_KW'
                    ],
                ],
            ],
            [
                'version' => '2.1.11',
                'variables' => [
                    'MAGENTO_CLOUD_VARIABLES' => [
                        'ADMIN_EMAIL' => 'admin@example.com',
                        'ADMIN_LOCALE' => 'fr_FR'
                    ],
                ],
            ],
            [
                'version' => '2.1.14',
                'variables' => [
                    'MAGENTO_CLOUD_VARIABLES' => [
                        'ADMIN_EMAIL' => 'admin@example.com',
                        'ADMIN_LOCALE' => 'ar_KW'
                    ],
                ],
            ],
            [
                'version' => '2.1.15',
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
