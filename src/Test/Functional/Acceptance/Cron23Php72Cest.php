<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Test\Functional\Acceptance;

/**
 * @group php72
 */
class Cron23Php72Cest extends CronCest
{
    /**
     * @return array
     */
    protected function cronDataProvider(): array
    {
        return [
            [
                'version' => '2.3.1',
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
