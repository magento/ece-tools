<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Unit\App\Logger;

use Magento\MagentoCloud\App\Logger\Pool;
use Magento\MagentoCloud\App\Logger\LineFormatterFactory;
use Monolog\Formatter\LineFormatter;
use Magento\MagentoCloud\App\Logger\HandlerFactory;
use Monolog\Handler\HandlerInterface;
use Magento\MagentoCloud\Config\Log as LogConfig;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @inheritdoc
 */
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
    protected function setUp()
    {
        $this->logConfigMock = $this->createMock(LogConfig::class);
        $this->lineFormatterFactoryMock = $this->createMock(LineFormatterFactory::class);
        $this->handlerFactoryMock = $this->createMock(HandlerFactory::class);

        $this->pool = new Pool($this->logConfigMock, $this->lineFormatterFactoryMock, $this->handlerFactoryMock);
    }

    /**
     * @throws \Exception
     */
    public function testGetHandlers()
    {
        $this->logConfigMock->expects($this->once())
            ->method('getHandlers')
            ->willReturn([
                'slack' => [],
                'email' => ['use_default_formatter' => false],
                'syslog' => ['use_default_formatter' => true]
            ]);

        $formatterMock = $this->createMock(LineFormatter::class);
        $this->lineFormatterFactoryMock->expects($this->exactly(2))
            ->method('create')
            ->willReturn($formatterMock);

        $slackHandlerMock = $this->getMockForAbstractClass(HandlerInterface::class);
        $slackHandlerMock->expects($this->once())
            ->method('setFormatter')
            ->with($formatterMock)
            ->willReturnSelf();
        $emailHandlerMock = $this->getMockForAbstractClass(HandlerInterface::class);
        $emailHandlerMock->expects($this->once())
            ->method('setFormatter')
            ->with($formatterMock)
            ->willReturnSelf();
        $syslogHandler = $this->getMockForAbstractClass(HandlerInterface::class);
        $syslogHandler->expects($this->never())
            ->method('setFormatter');

        $this->handlerFactoryMock->expects($this->exactly(3))
            ->method('create')
            ->withConsecutive(['slack'], ['email'], ['syslog'])
            ->willReturnOnConsecutiveCalls($slackHandlerMock, $emailHandlerMock, $syslogHandler);

        $this->pool->getHandlers();
        // Lazy load.
        $this->pool->getHandlers();
    }
}
