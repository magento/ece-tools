<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Unit\Process\Deploy;

use Magento\MagentoCloud\App\GenericException;
use Magento\MagentoCloud\Config\State;
use Magento\MagentoCloud\Process\Deploy\InstallUpdate;
use Magento\MagentoCloud\Process\ProcessException;
use PHPUnit\Framework\TestCase;
use PHPUnit_Framework_MockObject_MockObject as Mock;
use Psr\Log\LoggerInterface;

/**
 * @inheritdoc
 */
class InstallUpdateTest extends TestCase
{
    /**
     * @var LoggerInterface|Mock
     */
    private $loggerMock;

    /**
     * @var State|Mock
     */
    private $stateMock;

    /**
     * @var InstallUpdate\Install|Mock
     */
    private $installProcessMock;

    /**
     * @var InstallUpdate\Update|Mock
     */
    private $updateProcessMock;

    /**
     * @var InstallUpdate
     */
    private $process;

    protected function setUp()
    {
        $this->installProcessMock = $this->createMock(InstallUpdate\Install::class);
        $this->updateProcessMock = $this->createMock(InstallUpdate\Update::class);
        $this->stateMock = $this->createMock(State::class);
        $this->loggerMock = $this->getMockForAbstractClass(LoggerInterface::class);

        $this->process = new InstallUpdate(
            $this->loggerMock,
            $this->stateMock,
            $this->installProcessMock,
            $this->updateProcessMock
        );
    }

    /**
     * @throws ProcessException
     */
    public function testExecuteInstall()
    {
        $this->stateMock->expects($this->once())
            ->method('isInstalled')
            ->willReturn(false);
        $this->loggerMock->expects($this->exactly(2))
            ->method('notice')
            ->withConsecutive(
                ['Starting install.'],
                ['End of install.']
            );
        $this->installProcessMock->expects($this->once())
            ->method('execute');
        $this->updateProcessMock->expects($this->never())
            ->method('execute');

        $this->process->execute();
    }

    /**
     * @throws ProcessException
     */
    public function testExecuteUpdate()
    {
        $this->stateMock->expects($this->once())
            ->method('isInstalled')
            ->willReturn(true);
        $this->loggerMock->expects($this->exactly(2))
            ->method('notice')
            ->withConsecutive(
                ['Starting update.'],
                ['End of update.']
            );
        $this->installProcessMock->expects($this->never())
            ->method('execute');
        $this->updateProcessMock->expects($this->once())
            ->method('execute');

        $this->process->execute();
    }

    /**
     * @expectedExceptionMessage Some error
     * @expectedException \Magento\MagentoCloud\Process\ProcessException
     *
     * @throws ProcessException
     */
    public function testExecuteWithException()
    {
        $this->stateMock->expects($this->once())
            ->method('isInstalled')
            ->willThrowException(new GenericException('Some error'));

        $this->process->execute();
    }
}
