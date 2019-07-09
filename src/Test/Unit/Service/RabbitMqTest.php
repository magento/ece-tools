<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Test\Unit\Service;

use Magento\MagentoCloud\Config\Environment;
use Magento\MagentoCloud\Service\RabbitMq;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @inheritdoc
 */
class RabbitMqTest extends TestCase
{

    /**
     * @var RabbitMq|MockObject
     */
    private $rabbitMq;

    /**
     * @var Environment|MockObject
     */
    private $environmentMock;

    /**
     * @inheritdoc
     */
    public function setUp()
    {
        $this->environmentMock = $this->createMock(Environment::class);

        $this->rabbitMq = new RabbitMq(
            $this->environmentMock
        );
    }

    public function testGetConfiguration()
    {
        $this->environmentMock->expects($this->exactly(3))
            ->method('getRelationship')
            ->withConsecutive(['rabbitmq'], ['mq'], ['amqp'])
            ->willReturnOnConsecutiveCalls(
                [],
                [],
                [
                    [
                        'host' => '127.0.0.1',
                        'port' => '5672',
                    ]
                ]
            );

        $this->assertSame(
            [
                'host' => '127.0.0.1',
                'port' => '5672',
            ],
            $this->rabbitMq->getConfiguration()
        );
    }

    public function testGetVersion()
    {
        $this->environmentMock->expects($this->exactly(3))
            ->method('getRelationship')
            ->withConsecutive(['rabbitmq'], ['mq'], ['amqp'])
            ->willReturnOnConsecutiveCalls(
                [],
                [],
                [
                    [
                        'host' => '127.0.0.1',
                        'port' => '5672',
                        'type' => 'rabbitmq:3.7',
                    ]
                ]
            );

        $this->assertEquals('3.7', $this->rabbitMq->getVersion());
    }

    public function testGetVersionNotConfigured()
    {
        $this->environmentMock->expects($this->exactly(3))
            ->method('getRelationship')
            ->willReturn([]);

        $this->assertEquals('0', $this->rabbitMq->getVersion());
    }
}
