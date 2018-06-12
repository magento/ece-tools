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
use Magento\MagentoCloud\Config\GlobalSection;
use Magento\MagentoCloud\Config\Log as LogConfig;
use Monolog\Handler\HandlerInterface;
use Monolog\Handler\NativeMailerHandler;
use Monolog\Handler\SlackHandler;
use Monolog\Handler\StreamHandler;
use Monolog\Handler\SyslogHandler;
use Monolog\Handler\SyslogUdpHandler;
use Monolog\Logger;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @inheritdoc
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class HandlerFactoryTest extends TestCase
{
    /**
     * @var LevelResolver|MockObject
     */
    private $levelResolverMock;

    /**
     * @var LogConfig|MockObject
     */
    private $logConfigMock;

    /**
     * @var Repository|MockObject
     */
    private $repositoryMock;

    /**
     * @var GelfHandlerFactory|MockObject
     */
    private $gelfHandlerFactoryMock;

    /**
     * @var GlobalSection|MockObject
     */
    private $globalConfigMock;

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
        $this->globalConfigMock = $this->createMock(GlobalSection::class);

        $this->handlerFactory = new HandlerFactory(
            $this->levelResolverMock,
            $this->logConfigMock,
            $this->gelfHandlerFactoryMock,
            $this->globalConfigMock
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
        $minLevelOverride,
        string $expectedClass,
        int $expectedLevel
    ) {
        $this->logConfigMock->expects($this->once())
            ->method('get')
            ->with($handler)
            ->willReturn($this->repositoryMock);
        $this->repositoryMock->method('get')
            ->willReturnMap($repositoryMockReturnMap);
        $this->globalConfigMock->expects($this->once())
            ->method('get')
            ->with(GlobalSection::VAR_MIN_LOGGING_LEVEL)
            ->willReturn($minLevelOverride);
        $this->levelResolverMock
            ->method('resolve')
            ->willReturnMap([
                [LogConfig::LEVEL_NOTICE, Logger::NOTICE],
                [LogConfig::LEVEL_INFO, Logger::INFO],
                [LogConfig::LEVEL_WARNING, Logger::WARNING],
            ]);

        $handlerInstance = $this->handlerFactory->create($handler);

        $this->assertInstanceOf(HandlerInterface::class, $handlerInstance);
        $this->assertInstanceOf($expectedClass, $handlerInstance);
        $this->assertSame($expectedLevel, $handlerInstance->getLevel());
    }

    /**
     * @return array
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
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
                'minLevelOverride' => '',
                'expectedClass' => StreamHandler::class,
                'expectedLevel' => Logger::INFO,
            ],
            [
                'handler' => HandlerFactory::HANDLER_STREAM,
                'repositoryMockReturnMap' => [
                    ['stream', null, 'php://stdout'],
                    ['min_level', LogConfig::LEVEL_WARNING, LogConfig::LEVEL_WARNING],
                ],
                'minLevelOverride' => LogConfig::LEVEL_WARNING,
                'expectedClass' => StreamHandler::class,
                'expectedLevel' => Logger::WARNING,
            ],
            [
                'handler' => HandlerFactory::HANDLER_FILE,
                'repositoryMockReturnMap' => [
                    ['stream', null, 'var/log/cloud.log'],
                    ['min_level', LogConfig::LEVEL_NOTICE, LogConfig::LEVEL_NOTICE],
                    ['min_level', LogConfig::LEVEL_INFO, LogConfig::LEVEL_INFO],
                ],
                'minLevelOverride' => '',
                'expectedClass' => StreamHandler::class,
                'expectedLevel' => Logger::INFO,
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
                'minLevelOverride' => '',
                'expectedClass' => SlackHandler::class,
                'expectedLevel' => Logger::NOTICE,
            ],
            [
                'handler' => HandlerFactory::HANDLER_SLACK,
                'repositoryMockReturnMap' => [
                    ['token', null, 'someToken'],
                    ['channel', 'general', 'someChannel'],
                    ['username', 'Slack Log Notifier', 'someUser'],
                    ['min_level', LogConfig::LEVEL_WARNING, LogConfig::LEVEL_WARNING],
                ],
                'minLevelOverride' => LogConfig::LEVEL_WARNING,
                'expectedClass' => SlackHandler::class,
                'expectedLevel' => Logger::WARNING,
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
                'minLevelOverride' => '',
                'expectedClass' => NativeMailerHandler::class,
                'expectedLevel' => Logger::NOTICE,
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
                'minLevelOverride' => '',
                'expectedClass' => SyslogHandler::class,
                'expectedLevel' => Logger::NOTICE,
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
                'minLevelOverride' => '',
                'expectedClass' => SyslogUdpHandler::class,
                'expectedLevel' => Logger::NOTICE,
            ],
        ];
    }
}
