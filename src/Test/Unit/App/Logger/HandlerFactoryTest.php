<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Test\Unit\App\Logger;

use Illuminate\Config\Repository;
use Magento\MagentoCloud\App\Logger\Gelf\Handler as GelfHandler;
use Magento\MagentoCloud\App\Logger\Gelf\HandlerFactory as GelfHandlerFactory;
use Magento\MagentoCloud\App\Logger\HandlerFactory;
use Magento\MagentoCloud\Config\GlobalSection;
use Magento\MagentoCloud\Config\Log as LogConfig;
use Monolog\Handler\AbstractHandler;
use Monolog\Handler\HandlerInterface;
use Monolog\Handler\NativeMailerHandler;
use Monolog\Handler\SlackHandler;
use Monolog\Handler\StreamHandler;
use Monolog\Handler\SyslogHandler;
use Monolog\Handler\SyslogUdpHandler;
use Monolog\Level;
use Monolog\Logger;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @inheritdoc
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
#[AllowMockObjectsWithoutExpectations]
class HandlerFactoryTest extends TestCase
{
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
    protected function setUp(): void
    {
        $this->logConfigMock = $this->createMock(LogConfig::class);
        $this->repositoryMock = $this->createMock(Repository::class);
        $this->gelfHandlerFactoryMock = $this->createMock(GelfHandlerFactory::class);
        $this->globalConfigMock = $this->createMock(GlobalSection::class);

        $this->handlerFactory = new HandlerFactory(
            $this->logConfigMock,
            $this->gelfHandlerFactoryMock,
            $this->globalConfigMock
        );
    }

    /**
     * Test create with wrong handler from file.
     *
     * @return void
     */
    public function testCreateWithWrongHandlerFromFile(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Unknown type of log handler: someHandler');

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

        $this->handlerFactory->create($handler);
    }

    /**
     * Test create Gelf handler.
     *
     * @return void
     */
    public function testCreateGelfHandler(): void
    {
        $handler = 'gelf';
        $handlerMock = $this->createStub(GelfHandler::class);
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
     * Test create method.
     *
     * @param string $handlerName
     * @param array $repositoryMockReturnMap
     * @param $minLevelOverride
     * @param string $expectedClass
     * @param int $expectedLevel
     * @dataProvider createDataProvider
     * @throws \Exception
     */
    #[DataProvider('createDataProvider')]
    public function testCreate(
        string $handlerName,
        array $repositoryMockReturnMap,
        $minLevelOverride,
        string $expectedClass,
        int $expectedLevel
    ): void {
        $this->logConfigMock->expects($this->once())
            ->method('get')
            ->with($handlerName)
            ->willReturn($this->repositoryMock);
        $this->repositoryMock->method('get')
            ->willReturnMap($repositoryMockReturnMap);
        $this->globalConfigMock->expects($this->once())
            ->method('get')
            ->with(GlobalSection::VAR_MIN_LOGGING_LEVEL)
            ->willReturn($minLevelOverride);

        /** @var AbstractHandler $handler */
        $handler = $this->handlerFactory->create($handlerName);
        $level = $handler->getLevel();
        if ($level instanceof Level) {
            $level = $level->value;
        }
        $this->assertInstanceOf(HandlerInterface::class, $handler);
        $this->assertInstanceOf($expectedClass, $handler);
        $this->assertSame($expectedLevel, $level);
    }

    /**
     * Create data provider method.
     *
     * @return array
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public static function createDataProvider(): array
    {
        return [
            'stream handler' => [
                HandlerFactory::HANDLER_STREAM,
                [
                    ['stream', null, 'php://stdout'],
                    ['min_level', LogConfig::LEVEL_NOTICE, LogConfig::LEVEL_NOTICE],
                    ['min_level', LogConfig::LEVEL_INFO, LogConfig::LEVEL_INFO],
                ],
                '',
                StreamHandler::class,
                Logger::INFO,
            ],
            'stream handler 2' => [
                HandlerFactory::HANDLER_STREAM,
                [
                    ['stream', null, 'php://stdout'],
                    ['min_level', LogConfig::LEVEL_WARNING, LogConfig::LEVEL_WARNING],
                ],
                LogConfig::LEVEL_WARNING,
                StreamHandler::class,
                Logger::WARNING,
            ],
            'file handler default' => [
                HandlerFactory::HANDLER_FILE,
                [
                    ['file', null, 'var/log/cloud.log'],
                    ['min_level', LogConfig::LEVEL_NOTICE, LogConfig::LEVEL_DEBUG],
                    ['min_level', LogConfig::LEVEL_DEBUG, LogConfig::LEVEL_DEBUG],
                ],
                '',
                StreamHandler::class,
                Logger::DEBUG,
            ],
            'file error handler default' => [
                HandlerFactory::HANDLER_FILE_ERROR,
                [
                    ['file', null, 'var/log/cloud.error.log'],
                    ['min_level', LogConfig::LEVEL_NOTICE, LogConfig::LEVEL_NOTICE],
                    ['min_level', LogConfig::LEVEL_WARNING, LogConfig::LEVEL_WARNING],
                ],
                '',
                StreamHandler::class,
                Logger::WARNING,
            ],
            'file handler min_level overwritten' => [
                HandlerFactory::HANDLER_FILE,
                [
                    ['file', null, 'var/log/cloud.log'],
                    ['min_level', null, LogConfig::LEVEL_INFO],
                    ['min_level', null, LogConfig::LEVEL_INFO],
                ],
                '',
                StreamHandler::class,
                Logger::INFO,
            ],
            'file handler MIN_LOGGING_LEVEL overwritten' => [
                HandlerFactory::HANDLER_FILE,
                [
                    ['file', null, 'var/log/cloud.log'],
                    ['min_level', null, LogConfig::LEVEL_DEBUG]
                ],
                LogConfig::LEVEL_INFO,
                StreamHandler::class,
                Logger::DEBUG,
            ],
            'slack handler' => [
                HandlerFactory::HANDLER_SLACK,
                [
                    ['token', null, 'someToken'],
                    ['channel', 'general', 'someChannel'],
                    ['username', 'Slack Log Notifier', 'someUser'],
                    ['min_level', LogConfig::LEVEL_NOTICE, LogConfig::LEVEL_NOTICE],
                    ['min_level', LogConfig::LEVEL_INFO, LogConfig::LEVEL_INFO],
                ],
                '',
                SlackHandler::class,
                Logger::NOTICE,
            ],
            'slack handler 2' =>[
                HandlerFactory::HANDLER_SLACK,
                [
                    ['token', null, 'someToken'],
                    ['channel', 'general', 'someChannel'],
                    ['username', 'Slack Log Notifier', 'someUser'],
                    ['min_level', LogConfig::LEVEL_WARNING, LogConfig::LEVEL_WARNING],
                ],
                LogConfig::LEVEL_WARNING,
                SlackHandler::class,
                Logger::WARNING,
            ],
            'email handler' => [
                HandlerFactory::HANDLER_EMAIL,
                [
                    ['to', null, 'user@example.com'],
                    ['from', null, 'user2@example.com'],
                    ['subject', 'Log from Magento Cloud', 'someSubject'],
                    ['min_level', LogConfig::LEVEL_NOTICE, LogConfig::LEVEL_NOTICE],
                    ['min_level', LogConfig::LEVEL_INFO, LogConfig::LEVEL_INFO],
                ],
                '',
                NativeMailerHandler::class,
                Logger::NOTICE,
            ],
            'syslog handler' => [
                HandlerFactory::HANDLER_SYSLOG,
                [
                    ['ident', null, 'user@example.com'],
                    ['facility', LOG_USER, LOG_USER],
                    ['bubble', true, false],
                    ['logopts', LOG_PID, LOG_PERROR],
                    ['min_level', LogConfig::LEVEL_NOTICE, LogConfig::LEVEL_NOTICE],
                    ['min_level', LogConfig::LEVEL_INFO, LogConfig::LEVEL_INFO],
                ],
                '',
                SyslogHandler::class,
                Logger::NOTICE,
            ],
            'syslog udp handler' => [
                HandlerFactory::HANDLER_SYSLOG_UDP,
                [
                    ['host', null, '127.0.0.1'],
                    ['port', null, 12201],
                    ['facility', LOG_USER, LOG_USER],
                    ['bubble', true, false],
                    ['ident', 'php', 'php'],
                    ['min_level', LogConfig::LEVEL_NOTICE, LogConfig::LEVEL_NOTICE],
                    ['min_level', LogConfig::LEVEL_INFO, LogConfig::LEVEL_INFO],
                ],
                '',
                SyslogUdpHandler::class,
                Logger::NOTICE,
            ],
        ];
    }
}
