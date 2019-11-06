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
class ElasticSearch23Php72Cest extends ElasticSearchCest
{
    /**
     * @return array
     */
    protected function elasticDataProvider(): array
    {
        return [
            [
                'magento' => '2.3.0',
                'services' => [],
                'expectedResult' => ['engine' => 'mysql'],
            ],
            [
                'magento' => '2.3.0',
                'services' => ['es' => '5.2'],
                'expectedResult' => [
                    'engine' => 'elasticsearch5',
                    'elasticsearch5_server_hostname' => 'elasticsearch',
                    'elasticsearch5_server_port' => '9200'
                ],
            ],
            [
                'magento' => '2.3.2',
                'services' => ['es' => '6.5'],
                'expectedResult' => [
                    'engine' => 'elasticsearch6',
                    'elasticsearch6_server_hostname' => 'elasticsearch',
                    'elasticsearch6_server_port' => '9200'
                ],
            ],
        ];
    }
}
