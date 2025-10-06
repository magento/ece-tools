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

        $this->shellMock->expects($this->never())
            ->method('execute');
        $this->assertEquals('0', $this->activeMq->getVersion());
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
        $processMock->expects($this->once())
            ->method('getOutput')
            ->willReturn($version);
        $this->shellMock->expects($this->once())
            ->method('execute')
            ->with('dpkg -s activemq-artemis | grep Version')
            ->willReturn($processMock);

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
        $processMock->expects($this->once())
            ->method('getOutput')
            ->willReturn($version);
        
        $this->shellMock->expects($this->exactly(3))
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

        $this->assertEquals($expectedResult, $this->activeMq->getVersion());
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

        $this->shellMock->expects($this->exactly(4))
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
        $processMock->expects($this->once())
            ->method('getOutput')
            ->willReturn('Version: 2.42.1');

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
   * Test ActiveMQ version retrieval from artemis CLI command
   *
   * @return void
   * @throws ServiceException
   */
    public function testGetVersionFromArtemisCli(): void
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
        $processMock->expects($this->once())
            ->method('getOutput')
            ->willReturn('ActiveMQ Artemis 2.42.0');

        $this->shellMock->expects($this->exactly(4))
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

        $this->assertEquals('2.42', $this->activeMq->getVersion());
    }

  /**
   * Test STOMP availability detection (simplified - any ActiveMQ config enables STOMP)
   *
   * @param array $config
   * @param bool $expected
   * @return       void
   * @dataProvider isStompEnabledDataProvider
   */
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

