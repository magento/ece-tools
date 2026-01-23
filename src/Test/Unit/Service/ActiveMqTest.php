<?php

/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\MagentoCloud\Test\Unit\Service;

use Magento\MagentoCloud\Config\Environment;
use Magento\MagentoCloud\Service\ActiveMq;
use Magento\MagentoCloud\Service\ServiceException;
use Magento\MagentoCloud\Shell\ProcessInterface;
use Magento\MagentoCloud\Shell\ShellException;
use Magento\MagentoCloud\Shell\ShellInterface;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\TestCase;

/**
 * Test class for ActiveMQ service
 * @inheritdoc
 */
#[AllowMockObjectsWithoutExpectations]
class ActiveMqTest extends TestCase
{
    /**
     * ActiveMQ service instance
     *
     * @var ActiveMq
     */
    private ActiveMq $activeMq;

    /**
     * Environment mock instance
     *
     * @var Environment
     */
    private $environmentMock;

    /**
     * Shell interface mock instance
     *
     * @var ShellInterface
     */
    private $shellMock;

    /**
     * Set up test environment
     *
     * @return void
     * @throws Exception
     */
    public function setUp(): void
    {
        $this->environmentMock = $this->createMock(Environment::class);
        $this->shellMock = $this->getMockBuilder(ShellInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->activeMq = new ActiveMq(
            $this->environmentMock,
            $this->shellMock
        );
    }

    /**
     * Test ActiveMQ configuration retrieval
     *
     * @return void
     */
    public function testGetConfiguration(): void
    {
        $this->environmentMock->expects($this->exactly(4))
            ->method('getRelationship')
            // withConsecutive() alternative.
            ->willReturnCallback(
                fn($param) => match ([$param]) {
                    ['activemq'], ['activemq-artemis'], ['artemis'] => [],
                    ['amq'] => [
                        [
                            'host' => '127.0.0.1',
                            'port' => '61616',
                        ]
                    ]
                }
            );

        $this->assertSame(
            [
                'host' => '127.0.0.1',
                'port' => '61616',
            ],
            $this->activeMq->getConfiguration()
        );
    }

    /**
     * Test ActiveMQ version retrieval
     *
     * @return void
     * @throws ServiceException
     */
    public function testGetVersion(): void
    {
        $this->environmentMock->expects($this->exactly(5))
            ->method('getRelationship')
            // withConsecutive() alternative.
            ->willReturnCallback(
                fn($param) => match ([$param]) {
                    ['activemq'], ['activemq-artemis'], ['artemis'], ['amq'] => [],
                    ['jms'] => [
                        [
                            'host' => '127.0.0.1',
                            'port' => '61616',
                            'type' => 'activemq:6.0',
                        ]
                    ]
                }
            );

        $this->shellMock->expects($this->never())
            ->method('execute');
        $this->assertEquals('6.0', $this->activeMq->getVersion());
    }

    /**
     * Test ActiveMQ version when service is not installed
     *
     * @return void
     * @throws ServiceException
     */
    public function testGetVersionNotInstalled(): void
    {
        $this->environmentMock->expects($this->exactly(5))
            ->method('getRelationship')
            // withConsecutive() alternative.
            ->willReturnCallback(
                fn($param) => match ([$param]) {
                    ['activemq'], ['activemq-artemis'], ['artemis'], ['amq'], ['jms'] => []
                }
            );

        // No configuration found means no dpkg check is performed
        $this->shellMock->expects($this->never())
            ->method('execute');
        $this->assertEquals('0', $this->activeMq->getVersion());
    }

    /**
     * Test ActiveMQ version retrieval from CLI
     *
     * @param  string $version Version string from CLI
     * @param  string $expectedResult Expected parsed version
     * @dataProvider getVersionFromCliDataProvider
     * @return void
     * @throws ServiceException|Exception
     */
    #[DataProvider('getVersionFromCliDataProvider')]
    public function testGetVersionFromCli(
        string $version,
        string $expectedResult
    ): void {
        $this->environmentMock->expects($this->exactly(2))
            ->method('getRelationship')
            ->willReturnCallback(fn($param) => match ([$param]) {
                ['activemq'] => [],
                ['activemq-artemis'] => [[
                    'host' => '127.0.0.1',
                    'port' => '61616',
                ]]
            });

        $processMock = $this->getMockBuilder(ProcessInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $processMock->expects($this->any())
            ->method('getOutput')
            ->willReturn($version);

        // With refactored code, it will try dpkg first, then potentially dpkg artemis, then CLI commands
        $this->shellMock->expects($this->atLeastOnce())
            ->method('execute')
            ->willReturnCallback(function ($command) use ($processMock) {
                if ($command === 'dpkg -s activemq-artemis | grep Version') {
                    return $processMock;
                }
                throw new ShellException('Not called');
            });

        $this->assertEquals($expectedResult, $this->activeMq->getVersion());
    }

    /**
     * Data provider for testGetVersionFromCli
     *
     * @return array
     */
    public static function getVersionFromCliDataProvider(): array
    {
        return [
            ['Version: 2.42.1', '2.42'],
            ['Version:2.42.1', '2.42'],
            ['Version: 2.42.0', '2.42'],
            ['Version: some version', '0'],
            ['redis_version:abc', '0'],
            ['activemq:2.42.6', '0'],
            ['', '0'],
            ['error', '0'],
        ];
    }

    /**
     * Test ActiveMQ version retrieval when dpkg packages not found
     * (This test is no longer relevant as CLI commands were removed in refactoring)
     *
     * @return void
     * @throws ServiceException|Exception
     */
    public function testGetVersionWhenDpkgFails(): void
    {
        $this->environmentMock->expects($this->exactly(2))
            ->method('getRelationship')
            ->willReturnCallback(fn($param) => match ([$param]) {
                ['activemq'] => [],
                ['activemq-artemis'] => [[
                    'host' => '127.0.0.1',
                    'port' => '61616',
                ]]
            });
        
        // Both dpkg methods fail
        $this->shellMock->expects($this->exactly(2))
            ->method('execute')
            ->willThrowException(new ShellException('Package not found'));

        // Should return '0' when both dpkg methods fail
        $this->assertEquals('0', $this->activeMq->getVersion());
    }

    /**
     * Test ActiveMQ version retrieval when all dpkg methods fail
     *
     * @return void
     * @throws ServiceException
     */
    public function testGetVersionAllMethodsFail(): void
    {
        $this->environmentMock->expects($this->exactly(2))
            ->method('getRelationship')
            ->willReturnCallback(fn($param) => match ([$param]) {
                ['activemq'] => [],
                ['activemq-artemis'] => [[
                    'host' => '127.0.0.1',
                    'port' => '61616',
                ]]
            });

        // Both dpkg methods fail
        $this->shellMock->expects($this->exactly(2))
            ->method('execute')
            ->willThrowException(new ShellException('Command failed'));

        // Should return '0' instead of throwing exception
        $this->assertEquals('0', $this->activeMq->getVersion());
    }

    /**
     * Test ActiveMQ version retrieval from artemis dpkg package
     *
     * @return void
     * @throws ServiceException
     */
    public function testGetVersionFromArtemisDpkg(): void
    {
        $this->environmentMock->expects($this->exactly(2))
            ->method('getRelationship')
            ->willReturnCallback(fn($param) => match ([$param]) {
                ['activemq'] => [],
                ['activemq-artemis'] => [[
                    'host' => '127.0.0.1',
                    'port' => '61616',
                ]]
            });

        $processMock = $this->getMockBuilder(ProcessInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $processMock->expects($this->any())
            ->method('getOutput')
            ->willReturn('Version: 2.42.1');

        $this->shellMock->expects($this->atLeastOnce())
            ->method('execute')
            ->willReturnCallback(
                function ($command) use ($processMock) {
                    if ($command === 'dpkg -s activemq-artemis | grep Version') {
                        throw new ShellException('Package not found');
                    }
                    if ($command === 'dpkg -s artemis | grep Version') {
                        return $processMock;
                    }
                    throw new ShellException('Command not found');
                }
            );

        $this->assertEquals('2.42', $this->activeMq->getVersion());
    }

    /**
     * Test ActiveMQ version retrieval when only artemis dpkg succeeds (second package)
     *
     * @return void
     * @throws ServiceException
     */
    public function testGetVersionFromArtemisSecondPackage(): void
    {
        $this->environmentMock->expects($this->exactly(2))
            ->method('getRelationship')
            ->willReturnCallback(fn($param) => match ([$param]) {
                ['activemq'] => [],
                ['activemq-artemis'] => [[
                    'host' => '127.0.0.1',
                    'port' => '61616',
                ]]
            });

        $processMock = $this->getMockBuilder(ProcessInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $processMock->expects($this->any())
            ->method('getOutput')
            ->willReturn('Version: 2.42.0');

        $this->shellMock->expects($this->exactly(2))
            ->method('execute')
            ->willReturnCallback(
                function ($command) use ($processMock) {
                    if ($command === 'dpkg -s activemq-artemis | grep Version') {
                        throw new ShellException('Package not found');
                    }
                    if ($command === 'dpkg -s artemis | grep Version') {
                        return $processMock;
                    }
                    throw new ShellException('Command not found');
                }
            );

        $this->assertEquals('2.42', $this->activeMq->getVersion());
    }

    /**
     * Test STOMP availability detection (simplified - any ActiveMQ config enables STOMP)
     *
     * @param array $config
     * @param bool $expected
     * @dataProvider isStompEnabledDataProvider
     * @return void
     * @throws Exception
     */
    #[DataProvider('isStompEnabledDataProvider')]
    public function testIsStompEnabled(array $config, bool $expected): void
    {
        $this->environmentMock
            ->method('getRelationship')
            ->willReturnMap(
                [
                    ['activemq', $config ? [$config] : []],
                    ['activemq-artemis', []],
                    ['artemis', []],
                    ['amq', []],
                    ['jms', []],
                ]
            );

        $result = $this->activeMq->isStompEnabled();
        $this->assertEquals($expected, $result);
    }

    /**
     * Data provider for STOMP availability tests
     *
     * @return array
     */
    public static function isStompEnabledDataProvider(): array
    {
        return [
            'any activemq configuration enables stomp' => [
                [
                    'host' => 'activemq.example.com',
                    'port' => 61616,
                    'username' => 'admin',
                    'password' => 'secret'
                ],
                true
            ],
            'different activemq config also enables stomp' => [
                [
                    'host' => '127.0.0.1',
                    'port' => 5672
                ],
                true
            ],
            'minimal activemq config enables stomp' => [
                [
                    'host' => 'localhost'
                ],
                true
            ],
            'empty configuration disables stomp' => [
                [],
                false
            ]
        ];
    }
}
