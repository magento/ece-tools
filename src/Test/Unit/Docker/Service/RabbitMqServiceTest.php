<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Unit\Docker\Service;

use Magento\MagentoCloud\Docker\Service\RabbitMqService;
use PHPUnit\Framework\TestCase;

/**
 * @inheritdoc
 */
class RabbitMqServiceTest extends TestCase
{
    /**
     * @var RabbitMqService
     */
    private $service;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        $this->service = new RabbitMqService();
    }

    public function testGet()
    {
        $this->assertSame(['image' => 'rabbitmq:latest'], $this->service->get());
    }
}
