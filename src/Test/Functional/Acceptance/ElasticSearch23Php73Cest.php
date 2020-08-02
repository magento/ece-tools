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
class ElasticSearch23Php73Cest extends ElasticSearchCest
{
    /**
     * @return array
     */
    protected function elasticDataProvider(): array
    {
        return [
            [
                'magento' => '2.3.4',
                'removeES' => true,
                'expectedResult' => ['engine' => 'mysql'],
            ],
            [
                'magento' => '2.3.4',
                'removeES' => false,
                'expectedResult' => [
                    'engine' => 'elasticsearch6',
                    'elasticsearch6_server_hostname' => 'elasticsearch',
                    'elasticsearch6_server_port' => '9200'
                ],
            ],
        ];
    }
}
