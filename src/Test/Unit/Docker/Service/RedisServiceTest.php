<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Unit\Docker\Service;

use Magento\MagentoCloud\Docker\Service\RedisService;
use PHPUnit\Framework\TestCase;

/**
 * @inheritdoc
 */
class RedisServiceTest extends TestCase
{
    /**
     * @var RedisService
     */
    private $service;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        $this->service = new RedisService();
    }

    public function testGet()
    {
        $this->assertSame([
            'image' => 'magento/magento-cloud-docker-redis:latest',
            'volumes' => [
                '/data',
            ],
            'ports' => [
                6379,
            ],
        ], $this->service->get());
    }
}
