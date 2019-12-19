<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Test\Unit\Step\Deploy;

use Magento\MagentoCloud\App\GenericException;
use Magento\MagentoCloud\Config\State;
use Magento\MagentoCloud\Step\Deploy\InstallUpdate;
use Magento\MagentoCloud\Step\StepException;
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
    private $step;

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
    private $stepInstallMock;

    /**
     * @var StepInterface|MockObject
     */
    private $stepUpdateMock;

    /**
     * @inheritDoc
     */
    protected function setUp()
    {
        $this->stateMock = $this->createMock(State::class);
        $this->loggerMock = $this->getMockForAbstractClass(LoggerInterface::class);
        $this->stepInstallMock = $this->getMockForAbstractClass(StepInterface::class);
        $this->stepUpdateMock = $this->getMockForAbstractClass(StepInterface::class);

        $this->step = new InstallUpdate(
            $this->loggerMock,
            $this->stateMock,
            [$this->stepInstallMock],
            [$this->stepUpdateMock]
        );
    }

    /**
     * @throws StepException
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

        $this->stepInstallMock->expects($this->once())
            ->method('execute');
        $this->stepUpdateMock->expects($this->never())
            ->method('execute');

        $this->step->execute();
    }

    /**
     * @throws StepException
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
        $this->stepInstallMock->expects($this->never())
            ->method('execute');
        $this->stepUpdateMock->expects($this->once())
            ->method('execute');

        $this->step->execute();
    }

    /**
     * @throws StepException
     */
    public function testExecuteWithException()
    {
        $this->expectException(StepException::class);
        $this->expectExceptionMessage('Some error');

        $this->stateMock->expects($this->once())
            ->method('isInstalled')
            ->willThrowException(new GenericException('Some error'));

        $this->step->execute();
    }
}
