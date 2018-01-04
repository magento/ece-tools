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

        $this->assertSame(
            Wrapper::CODE_SUCCESS,
            $this->wrapper->execute($callback, $this->outputMock)
        );
    }
}
