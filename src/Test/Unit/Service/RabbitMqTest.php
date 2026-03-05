<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Test\Unit\Service;

use Magento\MagentoCloud\Config\Environment;
use Magento\MagentoCloud\Service\RabbitMq;
use Magento\MagentoCloud\Service\ServiceException;
use Magento\MagentoCloud\Shell\ProcessInterface;
use Magento\MagentoCloud\Shell\ShellException;
use Magento\MagentoCloud\Shell\ShellInterface;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @inheritdoc
 */
#[AllowMockObjectsWithoutExpectations]
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
     * @var ShellInterface|MockObject
     */
    private $shellMock;

    /**
     * @inheritdoc
     */
    public function setUp(): void
    {
        $this->environmentMock = $this->createMock(Environment::class);
        $this->shellMock = $this->createMock(ShellInterface::class);

        $this->rabbitMq = new RabbitMq(
            $this->environmentMock,
            $this->shellMock
        );
    }

    /**
     * Test get configuration.
     *
     * @return void
     */
    public function testGetConfiguration(): void
    {
        $this->environmentMock->expects($this->exactly(3))
            ->method('getRelationship')
            ->willReturnCallback(fn($param) => match ([$param]) {
                ['rabbitmq'] => [],
                ['mq']       => [],
                ['amqp']     => [
                    [
                        'host' => '127.0.0.1',
                        'port' => '5672',
                    ]
                ]
            });

        $this->assertSame(
            [
                'host' => '127.0.0.1',
                'port' => '5672',
            ],
            $this->rabbitMq->getConfiguration()
        );
    }

    /**
     * Test get version.
     *
     * @return void
     */
    public function testGetVersion(): void
    {
        $this->environmentMock->expects($this->exactly(3))
            ->method('getRelationship')
            // withConsecutive() alternative.
            ->willReturnCallback(fn($param) => match ([$param]) {
                ['rabbitmq'] => [],
                ['mq'] => [],
                ['amqp'] => [
                    [
                        'host' => '127.0.0.1',
                        'port' => '5672',
                        'type' => 'rabbitmq:3.7',
                    ]
                ]
            });

        $this->shellMock->expects($this->never())
            ->method('execute');
        $this->assertEquals('3.7', $this->rabbitMq->getVersion());
    }

    /**
     * Test get version not installed.
     *
     * @return void
     * @throws ServiceException
     */
    public function testGetVersionNotInstalled(): void
    {
        $series = [
            [['rabbitmq'], []],
            [['mq'], []],
            [['amqp'], []]
        ];
        $this->environmentMock->expects($this->exactly(3))
            ->method('getRelationship')
            ->willReturnCallback(fn($param) => match ([$param]) {
                ['rabbitmq'] => [],
                ['mq'] => [],
                ['amqp'] => []
            });

        $this->shellMock->expects($this->never())
            ->method('execute');
        $this->assertEquals('0', $this->rabbitMq->getVersion());
    }

    /**
     * Test get version from CLI.
     *
     * @param string $version
     * @param string $expectedResult
     * @dataProvider getVersionFromCliDataProvider
     * @return void
     * @throws ServiceException
     */
    #[DataProvider('getVersionFromCliDataProvider')]
    public function testGetVersionFromCli(string $version, string $expectedResult): void
    {
        $this->environmentMock->expects($this->once())
            ->method('getRelationship')
            ->with('rabbitmq')
            ->willReturn([[
                'host' => '127.0.0.1',
                'port' => '5672',
            ]]);

        $processMock = $this->createMock(ProcessInterface::class);
        $processMock->expects($this->once())
            ->method('getOutput')
            ->willReturn($version);
        $this->shellMock->expects($this->once())
            ->method('execute')
            ->with('dpkg -s rabbitmq-server | grep Version')
            ->willReturn($processMock);

        $this->assertEquals($expectedResult, $this->rabbitMq->getVersion());
    }

    /**
     * Data provider for get version from CLI.
     *
     * @return array
     */
    public static function getVersionFromCliDataProvider(): array
    {
        return [
            ['Version: 3.8.5', '3.8'],
            ['Version:3.8.5', '3.8'],
            ['Version: 111.222.333', '111.222'],
            ['Version: some version', '0'],
            ['redis_version:abc', '0'],
            ['redis:5.3.6', '0'],
            ['', '0'],
            ['error', '0'],
        ];
    }

    /**
     * Test get version with exception.
     *
     * @return void
     * @throws ServiceException
     */
    public function testGetVersionWithException(): void
    {
        $exceptionMessage = 'Some shell exception';
        $this->expectException(ServiceException::class);
        $this->expectExceptionMessage($exceptionMessage);

        $this->environmentMock->expects($this->once())
            ->method('getRelationship')
            ->with('rabbitmq')
            ->willReturn([[
                'host' => '127.0.0.1',
                'port' => '5672',
            ]]);

        $this->shellMock->expects($this->once())
            ->method('execute')
            ->willThrowException(new ShellException($exceptionMessage));
        $this->rabbitMq->getVersion();
    }
}
