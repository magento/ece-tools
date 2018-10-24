<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Unit\Docker\Service;

use Magento\MagentoCloud\Docker\Service\ElasticSearchService;
use PHPUnit\Framework\TestCase;

/**
 * @inheritdoc
 */
class ElasticSearchServiceTest extends TestCase
{
    /**
     * @var ElasticSearchService
     */
    private $service;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        $this->service = new ElasticSearchService();
    }

    public function testGet()
    {
        $this->assertSame(['image' => 'magento/magento-cloud-docker-elasticsearch:5.2'], $this->service->get());
    }
}
