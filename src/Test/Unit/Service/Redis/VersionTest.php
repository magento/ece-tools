<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Test\Unit\Service\Redis;

use ReflectionException;
use Magento\MagentoCloud\Service\Redis\Version;
use Magento\MagentoCloud\Service\ServiceException;
use Magento\MagentoCloud\Shell\ProcessInterface;
use Magento\MagentoCloud\Shell\ShellException;
use Magento\MagentoCloud\Shell\ShellInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @inheritDoc
 */
class VersionTest extends TestCase
{
    /**
     * @var Version
     */
    private $version;

    /**
     * @var ShellInterface|MockObject
     */
    private $shellMock;

    /**
     * @inheritDoc
     */
    public function setUp(): void
    {
        $this->shellMock = $this->getMockForAbstractClass(ShellInterface::class);
        $this->version = new Version($this->shellMock);
    }

    /**
     * @param array $config
     * @param string $expectedResult
     * @throws ServiceException
     * @dataProvider getVersionFromConfigDataProvider
     */
    public function testGetVersionFromConfig(array $config, string $expectedResult): void
    {
        $this->shellMock->expects($this->never())
            ->method('execute');

        $this->assertEquals($expectedResult, $this->version->getVersion($config));
    }

    /**
     * Data provider for testGetVersionFromConfig
     *
     * @return array
     */
    public function getVersionFromConfigDataProvider(): array
    {
        return [
            [
                [
                    'host' => '127.0.0.1',
                    'port' => '3306',
                    'type' => 'redis:10.2'
                ],
                '10.2'
            ],
            [
                [
                    'type' => 'redis:10.2.5'
                ],
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
     * @throws ServiceException
     * @throws \ReflectionException
     * @dataProvider getVersionFromCliDataProvider
     */
    public function testGetVersionFromCli(string $version, string $expectedResult): void
    {
        $processMock = $this->getMockForAbstractClass(ProcessInterface::class);
        $processMock->expects($this->once())
            ->method('getOutput')
            ->willReturn($version);
        $this->shellMock->expects($this->once())
            ->method('execute')
            ->with('redis-cli -p 3306 -h 127.0.0.1 info | grep redis_version')
            ->willReturn($processMock);

        $this->assertEquals(
            $expectedResult,
            $this->version->getVersion(
                [
                    'host' => '127.0.0.1',
                    'port' => '3306',
                ]
            )
        );
    }

    /**
     * Data provider for testGetVersionFromCli
     *
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

        $this->shellMock->expects($this->once())
            ->method('execute')
            ->willThrowException(new ShellException($exceptionMessage));
        $this->version->getVersion([
            'host' => '127.0.0.1',
            'port' => '3306',
        ]);
    }

    /**
     * @return array
     */
    public function getVersionWithPasswordDataProvider(): array
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

    /**
     * @param string $version
     * @param string $expectedResult
     * @throws ReflectionException
     * @throws ServiceException
     *
     * @dataProvider getVersionWithPasswordDataProvider
     */
    public function testGetVersionWithPassword(string $version, string $expectedResult): void
    {
        $processMock = $this->getMockForAbstractClass(ProcessInterface::class);
        $processMock->expects(self::once())
            ->method('getOutput')
            ->willReturn($version);
        $this->shellMock->expects(self::once())
            ->method('execute')
            ->with('redis-cli -p 3306 -h 127.0.0.1 -a test info | grep redis_version')
            ->willReturn($processMock);

        self::assertEquals(
            $expectedResult,
            $this->version->getVersion(
                [
                    'host' => '127.0.0.1',
                    'port' => '3306',
                    'password' => 'test'
                ]
            )
        );
    }
}
