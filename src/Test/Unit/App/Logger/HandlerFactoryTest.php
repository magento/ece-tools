<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Unit\App\Logger;

use Illuminate\Config\Repository;
use Magento\MagentoCloud\App\Logger\Gelf\Handler as GelfHandler;
use Magento\MagentoCloud\App\Logger\Gelf\HandlerFactory as GelfHandlerFactory;
use Magento\MagentoCloud\App\Logger\HandlerFactory;
use Magento\MagentoCloud\App\Logger\LevelResolver;
use Magento\MagentoCloud\Config\Log as LogConfig;
use Monolog\Handler\HandlerInterface;
use Monolog\Handler\NativeMailerHandler;
use Monolog\Handler\SlackHandler;
use Monolog\Handler\StreamHandler;
use Monolog\Handler\SyslogHandler;
use Monolog\Handler\SyslogUdpHandler;
use Monolog\Logger;
use PHPUnit\Framework\TestCase;
use PHPUnit_Framework_MockObject_MockObject as Mock;

/**
 * @inheritdoc
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class HandlerFactoryTest extends TestCase
{
    /**
     * @var LevelResolver|Mock
     */
    private $levelResolverMock;

    /**
     * @var LogConfig|Mock
     */
    private $logConfigMock;

    /**
     * @var Repository|Mock
     */
    private $repositoryMock;

    /**
     * @var GelfHandlerFactory|Mock
     */
    private $gelfHandlerFactoryMock;

    /**
     * @var HandlerFactory
     */
    private $handlerFactory;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        $this->levelResolverMock = $this->createMock(LevelResolver::class);
        $this->logConfigMock = $this->createMock(LogConfig::class);
        $this->repositoryMock = $this->createMock(Repository::class);
        $this->gelfHandlerFactoryMock = $this->createMock(GelfHandlerFactory::class);

        $this->handlerFactory = new HandlerFactory(
            $this->levelResolverMock,
            $this->logConfigMock,
            $this->gelfHandlerFactoryMock
        );
    }

    /**
     * @expectedException \Exception
     * @expectedExceptionMessage Unknown type of log handler: someHandler
     */
    public function testCreateWithWrongHandlerFromFile()
    {
        $handler = 'someHandler';
        $this->logConfigMock->expects($this->once())
            ->method('get')
            ->with($handler)
            ->willReturn($this->repositoryMock);
        $this->repositoryMock
            ->method('get')
            ->willReturnMap([
                ['min_level', LogConfig::LEVEL_NOTICE, LogConfig::LEVEL_NOTICE],
                ['min_level', LogConfig::LEVEL_INFO, LogConfig::LEVEL_INFO],
            ]);
        $this->levelResolverMock
            ->method('resolve')
            ->willReturnMap([
                [LogConfig::LEVEL_NOTICE, Logger::NOTICE],
                [LogConfig::LEVEL_INFO, Logger::INFO]
            ]);

        $this->handlerFactory->create($handler);
    }

    public function testCreateGelfHandler()
    {
        $handler = 'gelf';
        $handlerMock = $this->createMock(GelfHandler::class);
        $this->logConfigMock->expects($this->once())
            ->method('get')
            ->with($handler)
            ->willReturn($this->repositoryMock);
        $this->repositoryMock
            ->method('get')
            ->willReturnMap([
                ['min_level', LogConfig::LEVEL_NOTICE, LogConfig::LEVEL_NOTICE],
                ['min_level', LogConfig::LEVEL_INFO, LogConfig::LEVEL_INFO],
            ]);
        $this->gelfHandlerFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($handlerMock);

        $this->assertInstanceOf(GelfHandler::class, $this->handlerFactory->create($handler));
    }

    /**
     * @param string $handler
     * @param int $repositoryMockGetExpects
     * @param array $repositoryMockReturnMap
     * @param string $expectedClass
     * @dataProvider createDataProvider
     */
    public function testCreate(
        string $handler,
        array $repositoryMockReturnMap,
        string $expectedClass
    ) {
        $this->logConfigMock->expects($this->once())
            ->method('get')
            ->with($handler)
            ->willReturn($this->repositoryMock);
        $this->repositoryMock->method('get')
            ->willReturnMap($repositoryMockReturnMap);

        $handlerInstance = $this->handlerFactory->create($handler);

        $this->assertInstanceOf(HandlerInterface::class, $handlerInstance);
        $this->assertInstanceOf($expectedClass, $handlerInstance);
    }

    /**
     * @return array
     */
    public function createDataProvider()
    {
        return [
            [
                'handler' => HandlerFactory::HANDLER_STREAM,
                'repositoryMockReturnMap' => [
                    ['stream', null, 'php://stdout'],
                    ['min_level', LogConfig::LEVEL_NOTICE, LogConfig::LEVEL_NOTICE],
                    ['min_level', LogConfig::LEVEL_INFO, LogConfig::LEVEL_INFO],
                ],
                'expectedClass' => StreamHandler::class,
            ],
            [
                'handler' => HandlerFactory::HANDLER_FILE,
                'repositoryMockReturnMap' => [
                    ['stream', null, 'var/log/cloud.log'],
                    ['min_level', LogConfig::LEVEL_NOTICE, LogConfig::LEVEL_NOTICE],
                    ['min_level', LogConfig::LEVEL_INFO, LogConfig::LEVEL_INFO],
                ],
                'expectedClass' => StreamHandler::class,
            ],
            [
                'handler' => HandlerFactory::HANDLER_SLACK,
                'repositoryMockReturnMap' => [
                    ['token', null, 'someToken'],
                    ['channel', 'general', 'someChannel'],
                    ['username', 'Slack Log Notifier', 'someUser'],
                    ['min_level', LogConfig::LEVEL_NOTICE, LogConfig::LEVEL_NOTICE],
                    ['min_level', LogConfig::LEVEL_INFO, LogConfig::LEVEL_INFO],
                ],
                'expectedClass' => SlackHandler::class,
            ],
            [
                'handler' => HandlerFactory::HANDLER_EMAIL,
                'repositoryMockReturnMap' => [
                    ['to', null, 'user@example.com'],
                    ['from', null, 'user2@example.com'],
                    ['subject', 'Log form Magento Cloud', 'someSubject'],
                    ['min_level', LogConfig::LEVEL_NOTICE, LogConfig::LEVEL_NOTICE],
                    ['min_level', LogConfig::LEVEL_INFO, LogConfig::LEVEL_INFO],
                ],
                'expectedClass' => NativeMailerHandler::class,
            ],
            [
                'handler' => HandlerFactory::HANDLER_SYSLOG,
                'repositoryMockReturnMap' => [
                    ['ident', null, 'user@example.com'],
                    ['facility', LOG_USER, LOG_USER],
                    ['bubble', true, false],
                    ['logopts', LOG_PID, LOG_PERROR],
                    ['min_level', LogConfig::LEVEL_NOTICE, LogConfig::LEVEL_NOTICE],
                    ['min_level', LogConfig::LEVEL_INFO, LogConfig::LEVEL_INFO],
                ],
                'expectedClass' => SyslogHandler::class,
            ],
            [
                'handler' => HandlerFactory::HANDLER_SYSLOG_UDP,
                'repositoryMockReturnMap' => [
                    ['host', null, '127.0.0.1'],
                    ['port', null, 12201],
                    ['facility', LOG_USER, LOG_USER],
                    ['bubble', true, false],
                    ['ident', 'php', 'php'],
                    ['min_level', LogConfig::LEVEL_NOTICE, LogConfig::LEVEL_NOTICE],
                    ['min_level', LogConfig::LEVEL_INFO, LogConfig::LEVEL_INFO],
                ],
                'expectedClass' => SyslogUdpHandler::class,
            ]
        ];
    }
}
