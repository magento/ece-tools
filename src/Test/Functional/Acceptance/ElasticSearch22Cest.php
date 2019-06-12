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
class ElasticSearch22Cest extends ElasticSearchCest
{
    /**
     * @return array
     */
    protected function elasticDataProvider(): array
    {
        return [
            [
                'magento' => '2.2.8',
                'services' => [],
                'expectedResult' => ['engine' => 'mysql'],
            ],
            [
                'magento' => '2.2.8',
                'services' => ['es' => '2.4'],
                'expectedResult' => [
                    'engine' => 'elasticsearch',
                    'elasticsearch_server_hostname' => 'elasticsearch',
                    'elasticsearch_server_port' => '9200'
                ],
            ],
        ];
    }
}
