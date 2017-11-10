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
use PHPUnit\Framework\TestCase;
use PHPUnit_Framework_MockObject_MockObject as Mock;

/**
 * @inheritdoc
 */
class PoolTest extends TestCase
{
    /**
     * @var LogConfig|Mock
     */
    private $logConfigMock;

    /**
     * @var LineFormatterFactory|Mock
     */
    private $lineFormatterFactoryMock;

    /**
     * @var HandlerFactory|Mock
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

    public function testGetHandlers()
    {
        $this->logConfigMock->expects($this->once())
            ->method('getHandlers')
            ->willReturn(['slack', 'email']);

        $formatterMock = $this->createMock(LineFormatter::class);
        $this->lineFormatterFactoryMock->expects($this->exactly(2))
            ->method('create')
            ->willReturn($formatterMock);

        $slackHandlerMock = $this->getMockBuilder(HandlerInterface::class)
            ->getMockForAbstractClass();
        $slackHandlerMock->expects($this->once())
            ->method('setFormatter')
            ->with($formatterMock)
            ->willReturnSelf();
        $emailHandlerMock = $this->getMockBuilder(HandlerInterface::class)
            ->getMockForAbstractClass();
        $emailHandlerMock->expects($this->once())
            ->method('setFormatter')
            ->with($formatterMock)
            ->willReturnSelf();

        $this->handlerFactoryMock->expects($this->exactly(2))
            ->method('create')
            ->withConsecutive(['slack'], ['email'])
            ->willReturnOnConsecutiveCalls($slackHandlerMock, $emailHandlerMock);

        $this->pool->getHandlers();
    }
}
