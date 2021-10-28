<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Test\Unit\Service;

use Magento\MagentoCloud\Config\Environment;
use Magento\MagentoCloud\Service\Redis;
use Magento\MagentoCloud\Service\Redis\Version;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @inheritdoc
 */
class RedisTest extends TestCase
{
    /**
     * @var Redis
     */
    private $redis;

    /**
     * @var Environment|MockObject
     */
    private $environmentMock;

    /**
     * @var Version|MockObject
     */
    private $versionRetrieverMock;

    /**
     * @inheritdoc
     */
    public function setUp(): void
    {
        $this->environmentMock = $this->createMock(Environment::class);
        $this->versionRetrieverMock = $this->createMock(Version::class);

        $this->redis = new Redis(
            $this->environmentMock,
            $this->versionRetrieverMock
        );
    }

    public function testGetConfiguration(): void
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

    public function testGetSlaveConfiguration(): void
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

    public function testGetVersion(): void
    {
        $version = '1.1.1';
        $config = [['some config']];

        $this->environmentMock->expects($this->once())
            ->method('getRelationship')
            ->willReturn($config);

        $this->versionRetrieverMock->expects($this->once())
            ->method('getVersion')
            ->with($config[0])
            ->willReturn($version);
        $this->assertSame(
            $version,
            $this->redis->getVersion()
        );
    }
}
