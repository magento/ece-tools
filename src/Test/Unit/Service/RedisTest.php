<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Test\Unit\Service;

use Magento\MagentoCloud\Config\Environment;
use Magento\MagentoCloud\Service\Redis;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @inheritdoc
 */
class RedisTest extends TestCase
{

    /**
     * @var Redis|MockObject
     */
    private $redis;

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

        $this->redis = new Redis(
            $this->environmentMock
        );
    }

    public function testGetConfiguration()
    {
        $this->environmentMock->expects($this->once())
            ->method('getRelationship')
            ->with(Redis::RELATIONSHIP_KEY)
            ->willReturn([
                [
                    'host' => '127.0.0.1',
                    'port' => '3306',
                ]
            ]);

        $this->assertSame(
            [
                'host' => '127.0.0.1',
                'port' => '3306',
            ],
            $this->redis->getConfiguration()
        );
    }

    public function testGetSlaveConfiguration()
    {
        $this->environmentMock->expects($this->once())
            ->method('getRelationship')
            ->with(Redis::RELATIONSHIP_SLAVE_KEY)
            ->willReturn([
                [
                    'host' => '127.0.0.1',
                    'port' => '3307',
                ]
            ]);

        $this->assertSame(
            [
                'host' => '127.0.0.1',
                'port' => '3307',
            ],
            $this->redis->getSlaveConfiguration()
        );
    }

    public function testGetVersion()
    {
        $this->environmentMock->expects($this->once())
            ->method('getRelationship')
            ->with(Redis::RELATIONSHIP_KEY)
            ->willReturn([
                [
                    'host' => '127.0.0.1',
                    'port' => '3306',
                    'type' => 'mysql:10.2',
                ]
            ]);

        $this->assertEquals('10.2', $this->redis->getVersion());
    }

    public function testGetVersionNotConfigured()
    {
        $this->environmentMock->expects($this->once())
            ->method('getRelationship')
            ->with(Redis::RELATIONSHIP_KEY)
            ->willReturn([]);

        $this->assertEquals('0', $this->redis->getVersion());
    }
}
