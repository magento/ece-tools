<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 *
 * @category Magento
 * @package  Magento\MagentoCloud\Test\Unit\Service
 * @author   Magento Core Team <core@magentocommerce.com>
 * @license  https://opensource.org/licenses/OSL-3.0 Open Software License (OSL 3.0)
 * @link     https://magento.com
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Test\Unit\Service;

use Magento\MagentoCloud\Config\Environment;
use Magento\MagentoCloud\Service\ActiveMq;
use Magento\MagentoCloud\Service\ServiceException;
use Magento\MagentoCloud\Shell\ProcessInterface;
use Magento\MagentoCloud\Shell\ShellException;
use Magento\MagentoCloud\Shell\ShellInterface;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Test class for ActiveMQ service
 *
 * @category Magento
 * @package  Magento\MagentoCloud\Test\Unit\Service
 * @author   Magento Core Team <core@magentocommerce.com>
 * @license  https://opensource.org/licenses/OSL-3.0 Open Software License (OSL 3.0)
 * @link     https://magento.com
 */
class ActiveMqTest extends TestCase
{

    /**
     * ActiveMQ service instance
     *
     * @var ActiveMq|MockObject
     */
    private ActiveMq|MockObject $_activeMq;

    /**
     * Environment mock instance
     *
     * @var Environment|MockObject
     */
    private $_environmentMock;

    /**
     * Shell interface mock instance
     *
     * @var ShellInterface|MockObject
     */
    private $_shellMock;

    /**
     * Set up test environment
     *
     * @return void
     * @throws Exception
     */
    public function setUp(): void
    {
        $this->_environmentMock = $this->createMock(Environment::class);
        $this->_shellMock = $this->getMockBuilder(ShellInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->_activeMq = new ActiveMq(
            $this->_environmentMock,
            $this->_shellMock
        );
    }

    /**
     * Test ActiveMQ configuration retrieval
     *
     * @return void
     */
    public function testGetConfiguration(): void
    {
        $this->_environmentMock->expects($this->exactly(3))
            ->method('getRelationship')
            // withConsecutive() alternative.
            ->willReturnCallback(
                fn($param) => match ([$param]) {
                ['activemq-artemis'], ['amq'] => [],
                ['jms'] => [
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
            $this->_activeMq->getConfiguration()
        );
    }

    /**
     * Test ActiveMQ version retrieval
     *
     * @return void
     */
    public function testGetVersion(): void
    {
        $this->_environmentMock->expects($this->exactly(3))
            ->method('getRelationship')
            // withConsecutive() alternative.
            ->willReturnCallback(
                fn($param) => match ([$param]) {
                ['activemq-artemis'], ['amq'] => [],
                ['jms'] => [
                    [
                        'host' => '127.0.0.1',
                        'port' => '61616',
                        'type' => 'activemq:6.0',
                    ]
                ]
                }
            );

        $this->_shellMock->expects($this->never())
            ->method('execute');
        $this->assertEquals('6.0', $this->_activeMq->getVersion());
    }

    /**
     * Test ActiveMQ version when service is not installed
     *
     * @return void
     * @throws ServiceException
     */
    public function testGetVersionNotInstalled(): void
    {
        $this->_environmentMock->expects($this->exactly(3))
            ->method('getRelationship')
            // withConsecutive() alternative.
            ->willReturnCallback(
                fn($param) => match ([$param]) {
                ['activemq-artemis'], ['amq'], ['jms'] => []
                }
            );

        $this->_shellMock->expects($this->never())
            ->method('execute');
        $this->assertEquals('0', $this->_activeMq->getVersion());
    }

    /**
     * Test ActiveMQ version retrieval from CLI
     *
     * @param  string $version        Version string from CLI
     * @param  string $expectedResult Expected parsed version
     * @return void
     * @throws ServiceException|Exception
     *
     * @dataProvider getVersionFromCliDataProvider
     */
    public function testGetVersionFromCli(
        string $version,
        string $expectedResult
    ): void {
        $this->_environmentMock->expects($this->once())
            ->method('getRelationship')
            ->with('activemq-artemis')
            ->willReturn(
                [[
                'host' => '127.0.0.1',
                'port' => '61616',
                ]]
            );

        $processMock = $this->getMockBuilder(ProcessInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $processMock->expects($this->once())
            ->method('getOutput')
            ->willReturn($version);
        $this->_shellMock->expects($this->once())
            ->method('execute')
            ->with('dpkg -s activemq-artemis | grep Version')
            ->willReturn($processMock);

        $this->assertEquals($expectedResult, $this->_activeMq->getVersion());
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
     * Test ActiveMQ version retrieval from activemq command
     *
     * @param  string $version        Version string from activemq command
     * @param  string $expectedResult Expected parsed version
     * @return void
     * @throws ServiceException|Exception
     *
     * @dataProvider getVersionFromActiveMqCommandDataProvider
     */
    public function testGetVersionFromActiveMqCommand(
        string $version,
        string $expectedResult
    ): void {
        $this->_environmentMock->expects($this->once())
            ->method('getRelationship')
            ->with('activemq-artemis')
            ->willReturn(
                [[
                'host' => '127.0.0.1',
                'port' => '61616',
                ]]
            );

        $processMock = $this->getMockBuilder(ProcessInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $processMock->expects($this->once())
            ->method('getOutput')
            ->willReturn($version);
        
        $this->_shellMock->expects($this->exactly(3))
            ->method('execute')
            ->willReturnCallback(
                function ($command) use ($processMock) {
                    if ($command === 'dpkg -s activemq-artemis | grep Version') {
                        throw new ShellException('Package not found');
                    }
                    if ($command === 'dpkg -s artemis | grep Version') {
                        throw new ShellException('Package not found');
                    }
                    if ($command === 'activemq-artemis --version 2>/dev/null | head -1') {
                        return $processMock;
                    }
                    throw new ShellException('Command not found');
                }
            );

        $this->assertEquals($expectedResult, $this->_activeMq->getVersion());
    }

    /**
     * Data provider for testGetVersionFromActiveMqCommand
     *
     * @return array
     */
    public static function getVersionFromActiveMqCommandDataProvider(): array
    {
        return [
                          ['ActiveMQ Artemis 2.42.1', '2.42'],
                ['ActiveMQ Artemis 2.42.0', '2.42'],
                ['ActiveMQ 2.42.5', '2.42'],
          ['Some other output', '0'],
          ['', '0'],
        ];
    }

    /**
     * Test ActiveMQ version retrieval when all detection methods fail
     *
     * @return void
     */
    public function testGetVersionAllMethodsFail(): void
    {
        $this->_environmentMock->expects($this->once())
            ->method('getRelationship')
            ->with('activemq-artemis')
            ->willReturn(
                [[
                'host' => '127.0.0.1',
                'port' => '61616',
                ]]
            );

        $this->_shellMock->expects($this->exactly(4))
            ->method('execute')
            ->willThrowException(new ShellException('Command failed'));
        
        // Should return '0' instead of throwing exception
        $this->assertEquals('0', $this->_activeMq->getVersion());
    }

    /**
     * Test ActiveMQ version retrieval from artemis dpkg package
     *
     * @return void
     */
    public function testGetVersionFromArtemisDpkg(): void
    {
        $this->_environmentMock->expects($this->once())
            ->method('getRelationship')
            ->with('activemq-artemis')
            ->willReturn(
                [[
                'host' => '127.0.0.1',
                'port' => '61616',
                ]]
            );

        $processMock = $this->getMockBuilder(ProcessInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $processMock->expects($this->once())
            ->method('getOutput')
            ->willReturn('Version: 2.42.1');

        $this->_shellMock->expects($this->exactly(2))
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

        $this->assertEquals('2.42', $this->_activeMq->getVersion());
    }

    /**
     * Test ActiveMQ version retrieval from artemis CLI command  
     *
     * @return void
     */
    public function testGetVersionFromArtemisCli(): void
    {
        $this->_environmentMock->expects($this->once())
            ->method('getRelationship')
            ->with('activemq-artemis')
            ->willReturn(
                [[
                'host' => '127.0.0.1',
                'port' => '61616',
                ]]
            );

        $processMock = $this->getMockBuilder(ProcessInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $processMock->expects($this->once())
            ->method('getOutput')
            ->willReturn('ActiveMQ Artemis 2.42.0');

        $this->_shellMock->expects($this->exactly(4))
            ->method('execute')
            ->willReturnCallback(
                function ($command) use ($processMock) {
                    if ($command === 'dpkg -s activemq-artemis | grep Version') {
                        throw new ShellException('Package not found');
                    }
                    if ($command === 'dpkg -s artemis | grep Version') {
                        throw new ShellException('Package not found');
                    }
                    if ($command === 'activemq-artemis --version 2>/dev/null | head -1') {
                        throw new ShellException('Command not found');
                    }
                    if ($command === 'artemis version 2>/dev/null | head -1') {
                        return $processMock;
                    }
                    throw new ShellException('Command not found');
                }
            );

        $this->assertEquals('2.42', $this->_activeMq->getVersion());
    }

    /**
     * Test STOMP availability detection (simplified - any ActiveMQ config enables STOMP)
     *
     * @return void
     * @dataProvider isStompEnabledDataProvider
     */
    public function testIsStompEnabled(array $config, bool $expected): void
    {
        $this->_environmentMock
            ->method('getRelationship')
            ->willReturnMap([
                ['activemq-artemis', $config ? [$config] : []],
                ['artemis', []],
                ['amq', []],
                ['jms', []],
            ]);

        $result = $this->_activeMq->isStompEnabled();
        $this->assertEquals($expected, $result);
    }

    /**
     * Data provider for STOMP availability tests
     *
     * @return array
     */
    public function isStompEnabledDataProvider(): array
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
