<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Unit\App\Command;

use Magento\MagentoCloud\App\Command\Wrapper;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use PHPUnit_Framework_MockObject_MockObject as Mock;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @inheritdoc
 */
class WrapperTest extends TestCase
{
    /**
     * @var Wrapper
     */
    private $wrapper;

    /**
     * @var LoggerInterface|Mock
     */
    private $loggerMock;

    /**
     * @var OutputInterface|Mock
     */
    private $outputMock;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        $this->loggerMock = $this->getMockForAbstractClass(LoggerInterface::class);
        $this->outputMock = $this->getMockForAbstractClass(OutputInterface::class);

        $this->wrapper = new Wrapper(
            $this->loggerMock
        );
    }

    /**
     * @inheritdoc
     */
    public function testExecute()
    {
        $callback = function () {
        };

        $this->loggerMock->expects($this->once())
            ->method('debug');
        $this->loggerMock->expects($this->never())
            ->method('critical');

        $this->assertSame(
            Wrapper::CODE_SUCCESS,
            $this->wrapper->execute($callback, $this->outputMock)
        );
    }

    public function testExecuteWithException()
    {
        $callback = function () {
            throw new \RuntimeException('Some error');
        };

        $this->loggerMock->expects($this->never())
            ->method('debug');
        $this->loggerMock->expects($this->once())
            ->method('critical')
            ->with('Some error');
        $this->outputMock->expects($this->once())
            ->method('writeln')
            ->with('<error>Some error</error>');

        $this->assertSame(
            Wrapper::CODE_FAILURE,
            $this->wrapper->execute($callback, $this->outputMock)
        );
    }

    public function testExecuteWithCustomCodeInException()
    {
        $callback = function () {
            throw new \RuntimeException('Some error', 5);
        };

        $this->loggerMock->expects($this->never())
            ->method('debug');
        $this->loggerMock->expects($this->once())
            ->method('critical')
            ->with('Some error');
        $this->outputMock->expects($this->once())
            ->method('writeln')
            ->with('<error>Some error</error>');

        $this->assertSame(
            5,
            $this->wrapper->execute($callback, $this->outputMock)
        );
    }
}
