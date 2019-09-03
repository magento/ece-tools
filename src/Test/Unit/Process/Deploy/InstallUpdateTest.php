<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Unit\Process\Deploy;

use Magento\MagentoCloud\App\GenericException;
use Magento\MagentoCloud\Config\State;
use Magento\MagentoCloud\Step\Deploy\InstallUpdate;
use Magento\MagentoCloud\Step\ProcessException;
use Magento\MagentoCloud\Step\StepInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * @inheritdoc
 */
class InstallUpdateTest extends TestCase
{
    /**
     * @var StepInterface
     */
    private $process;

    /**
     * @var LoggerInterface|MockObject
     */
    private $loggerMock;

    /**
     * @var State|MockObject
     */
    private $stateMock;

    /**
     * @var StepInterface|MockObject
     */
    private $processInstallMock;

    /**
     * @var StepInterface|MockObject
     */
    private $processUpdateMock;

    /**
     * @inheritDoc
     */
    protected function setUp()
    {
        $this->stateMock = $this->createMock(State::class);
        $this->loggerMock = $this->getMockForAbstractClass(LoggerInterface::class);
        $this->processInstallMock = $this->getMockForAbstractClass(StepInterface::class);
        $this->processUpdateMock = $this->getMockForAbstractClass(StepInterface::class);

        $this->process = new InstallUpdate(
            $this->loggerMock,
            $this->stateMock,
            [$this->processInstallMock],
            [$this->processUpdateMock]
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

        $this->processInstallMock->expects($this->once())
            ->method('execute');
        $this->processUpdateMock->expects($this->never())
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
        $this->processInstallMock->expects($this->never())
            ->method('execute');
        $this->processUpdateMock->expects($this->once())
            ->method('execute');

        $this->process->execute();
    }

    /**
     * @expectedExceptionMessage Some error
     * @expectedException \Magento\MagentoCloud\Step\ProcessException
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
