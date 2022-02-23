<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Test\Unit\App\Logger;

use Magento\MagentoCloud\App\Error;
use Magento\MagentoCloud\App\Logger\Formatter\JsonErrorFormatter;
use Magento\MagentoCloud\App\Logger\Pool;
use Magento\MagentoCloud\App\Logger\LineFormatterFactory;
use Magento\MagentoCloud\App\LoggerException;
use Magento\MagentoCloud\App\Logger\Formatter\LineFormatter;
use Magento\MagentoCloud\App\Logger\HandlerFactory;
use Magento\MagentoCloud\Config\Log as LogConfig;
use Monolog\Handler\AbstractProcessingHandler;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Yaml\Exception\ParseException;

class PoolTest extends TestCase
{
    /**
     * @var LogConfig|MockObject
     */
    private $logConfigMock;

    /**
     * @var LineFormatterFactory|MockObject
     */
    private $lineFormatterFactoryMock;

    /**
     * @var HandlerFactory|MockObject
     */
    private $handlerFactoryMock;

    /**
     * @var Pool
     */
    private $pool;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->logConfigMock = $this->createMock(LogConfig::class);
        $this->lineFormatterFactoryMock = $this->createMock(LineFormatterFactory::class);
        $this->handlerFactoryMock = $this->createMock(HandlerFactory::class);

        $this->pool = new Pool($this->logConfigMock, $this->lineFormatterFactoryMock, $this->handlerFactoryMock);
    }

    /**
     * @throws \Exception
     */
    public function testGetHandlers(): void
    {
        $jsonErrorFormatterMock = $this->createMock(JsonErrorFormatter::class);
        $this->logConfigMock->expects($this->once())
            ->method('getHandlers')
            ->willReturn([
                'slack' => [],
                'email' => ['use_default_formatter' => false],
                'syslog' => ['use_default_formatter' => true],
                'error-logger' => ['formatter' => $jsonErrorFormatterMock]
            ]);

        $formatterMock = $this->createMock(LineFormatter::class);
        $this->lineFormatterFactoryMock->expects($this->exactly(2))
            ->method('create')
            ->willReturn($formatterMock);

        $slackHandlerMock = $this->getMockForAbstractClass(
            AbstractProcessingHandler::class,
            [],
            '',
            true,
            true,
            true,
            ['setFormatter']
        );
        $slackHandlerMock->expects($this->once())
            ->method('setFormatter')
            ->with($formatterMock)
            ->willReturnSelf();
        $emailHandlerMock = $this->getMockForAbstractClass(
            AbstractProcessingHandler::class,
            [],
            '',
            true,
            true,
            true,
            ['setFormatter']
        );
        $emailHandlerMock->expects($this->once())
            ->method('setFormatter')
            ->with($formatterMock)
            ->willReturnSelf();
        $syslogHandler = $this->getMockForAbstractClass(
            AbstractProcessingHandler::class,
            [],
            '',
            true,
            true,
            true,
            ['setFormatter']
        );
        $syslogHandler->expects($this->never())
            ->method('setFormatter');
        $errorHandler = $this->getMockForAbstractClass(
            AbstractProcessingHandler::class,
            [],
            '',
            true,
            true,
            true,
            ['setFormatter']
        );
        $errorHandler->expects($this->once())
            ->method('setFormatter')
            ->with($jsonErrorFormatterMock)
            ->willReturnSelf();

        $this->handlerFactoryMock->expects($this->exactly(4))
            ->method('create')
            ->withConsecutive(['slack'], ['email'], ['syslog'], ['error-logger'])
            ->willReturnOnConsecutiveCalls($slackHandlerMock, $emailHandlerMock, $syslogHandler, $errorHandler);

        $this->pool->getHandlers();
        // Lazy load.
        $this->pool->getHandlers();
    }

    public function testWithParseException()
    {
        $this->expectExceptionMessage('some error');
        $this->expectExceptionCode(Error::BUILD_CONFIG_PARSE_FAILED);
        $this->expectException(LoggerException::class);

        $this->logConfigMock->expects($this->once())
            ->method('getHandlers')
            ->willThrowException(new ParseException('some error'));

        $this->pool->getHandlers();
    }

    public function testWithException()
    {
        $this->expectExceptionMessage('some error');
        $this->expectExceptionCode(101);
        $this->expectException(LoggerException::class);

        $this->logConfigMock->expects($this->once())
            ->method('getHandlers')
            ->willThrowException(new \Exception('some error', 101));

        $this->pool->getHandlers();
    }
}
