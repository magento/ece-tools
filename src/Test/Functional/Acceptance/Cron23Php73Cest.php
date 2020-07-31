<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Test\Functional\Acceptance;

/**
 * @group php73
 */
class Cron23Php73Cest extends CronCest
{
    /**
     * @var boolean
     */
    protected $removeEs = true;

    /**
     * @return array
     */
    protected function cronDataProvider(): array
    {
        return [
            [
                'version' => '2.3.4',
                'variables' => [
                    'MAGENTO_CLOUD_VARIABLES' => [
                        'ADMIN_EMAIL' => 'admin@example.com',
                        'ADMIN_LOCALE' => 'fr_FR'
                    ],
                ],
            ],
            [
                'version' => '2.3.3',
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
