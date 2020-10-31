<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Test\Unit\Service;

use Magento\MagentoCloud\Config\Environment;
use Magento\MagentoCloud\Service\Redis;
use Magento\MagentoCloud\Service\ServiceException;
use Magento\MagentoCloud\Shell\ProcessInterface;
use Magento\MagentoCloud\Shell\ShellException;
use Magento\MagentoCloud\Shell\ShellInterface;
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
     * @var ShellInterface|MockObject
     */
    private $shellMock;

    /**
     * @inheritdoc
     */
    public function setUp()
    {
        $this->environmentMock = $this->createMock(Environment::class);
        $this->shellMock = $this->getMockForAbstractClass(ShellInterface::class);

        $this->redis = new Redis(
            $this->environmentMock,
            $this->shellMock
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

    /**
     * @param array $config
     * @param string $expectedResult
     * @throws \Magento\MagentoCloud\Service\ServiceException
     * @throws \ReflectionException
     *
     * @dataProvider getVersionFromConfigDataProvider
     */
    public function testGetVersionFromConfig(array $config, string $expectedResult): void
    {
        $this->environmentMock->expects($this->once())
            ->method('getRelationship')
            ->with(Redis::RELATIONSHIP_KEY)
            ->willReturn($config);

        $this->shellMock->expects($this->never())
            ->method('execute');

        $this->assertEquals($expectedResult, $this->redis->getVersion());
    }

    /**
     * Data provider for testGetVersionFromConfig
     * @return array
     */
    public function getVersionFromConfigDataProvider(): array
    {
        return [
            [
                [[
                    'host' => '127.0.0.1',
                    'port' => '3306',
                    'type' => 'redis:10.2'
                ]],
                '10.2'
            ],
            [
                [[
                    'type' => 'redis:10.2.5'
                ]],
                '10.2.5'
            ],
            [
                [],
                '0'
            ],
        ];
    }

    /**
     * @param string $version
     * @param string $expectedResult
     * @throws \Magento\MagentoCloud\Service\ServiceException
     * @throws \ReflectionException
     *
     * @dataProvider getVersionFromCliDataProvider
     */
    public function testGetVersionFromCli(string $version, string $expectedResult): void
    {
        $this->environmentMock->expects($this->once())
            ->method('getRelationship')
            ->with(Redis::RELATIONSHIP_KEY)
            ->willReturn([[
                'host' => '127.0.0.1',
                'port' => '3306',
            ]]);

        $processMock = $this->getMockForAbstractClass(ProcessInterface::class);
        $processMock->expects($this->once())
            ->method('getOutput')
            ->willReturn($version);
        $this->shellMock->expects($this->once())
            ->method('execute')
            ->with('redis-cli -p 3306 -h 127.0.0.1 info | grep redis_version')
            ->willReturn($processMock);

        $this->assertEquals($expectedResult, $this->redis->getVersion());
    }

    /**
     * Data provider for testGetVersionFromCli
     * @return array
     */
    public function getVersionFromCliDataProvider(): array
    {
        return [
            ['redis_version:5.3.6', '5.3'],
            ['redis_version:1.2.3.4.5', '1.2'],
            ['redis_version:abc', '0'],
            ['redis:5.3.6', '0'],
            ['', '0'],
            ['error', '0'],
        ];
    }

    public function testGetVersionWithException()
    {
        $exceptionMessage = 'Some shell exception';
        $this->expectException(ServiceException::class);
        $this->expectExceptionMessage($exceptionMessage);

        $this->environmentMock->expects($this->once())
            ->method('getRelationship')
            ->with(Redis::RELATIONSHIP_KEY)
            ->willReturn([[
                'host' => '127.0.0.1',
                'port' => '3306',
            ]]);

        $this->shellMock->expects($this->once())
            ->method('execute')
            ->willThrowException(new ShellException($exceptionMessage));
        $this->redis->getVersion();
    }
}
