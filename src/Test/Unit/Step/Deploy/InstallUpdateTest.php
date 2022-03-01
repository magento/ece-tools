<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Test\Unit\Step\Deploy;

use Magento\MagentoCloud\App\GenericException;
use Magento\MagentoCloud\Config\State;
use Magento\MagentoCloud\Filesystem\Flag\Manager as FlagManager;
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
     * @var FlagManager|MockObject
     */
    private $flagManagerMock;

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
    protected function setUp(): void
    {
        $this->stateMock = $this->createMock(State::class);
        $this->loggerMock = $this->getMockForAbstractClass(LoggerInterface::class);
        $this->flagManagerMock = $this->createMock(FlagManager::class);
        $this->stepInstallMock = $this->getMockForAbstractClass(StepInterface::class);
        $this->stepUpdateMock = $this->getMockForAbstractClass(StepInterface::class);

        $this->step = new InstallUpdate(
            $this->loggerMock,
            $this->stateMock,
            $this->flagManagerMock,
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
        $this->flagManagerMock->expects($this->once())
            ->method('delete')
            ->with(FlagManager::FLAG_ENV_FILE_ABSENCE);

        $this->step->execute();
    }

    /**
     * @throws StepException
     */
    public function testExecuteUpdate()
    {
        $this->mainExpectsForUpdate();
        $this->flagManagerMock->expects($this->once())
            ->method('exists')
            ->with(FlagManager::FLAG_ENV_FILE_ABSENCE)
            ->willReturn(false);

        $this->step->execute();
    }

    /**
     * @throws StepException
     */
    public function testExecuteUpdateWhenFileIsAbsent()
    {
        $this->mainExpectsForUpdate();
        $this->flagManagerMock->expects($this->once())
            ->method('exists')
            ->with(FlagManager::FLAG_ENV_FILE_ABSENCE)
            ->willReturn(true);
        $this->loggerMock->expects($this->once())
            ->method('warning')
            ->with('Magento state indicated as installed'
                . ' but configuration file app/etc/env.php was empty or did not exist.'
                . ' Required data will be restored from environment configurations'
                . ' and from .magento.env.yaml file.');

        $this->step->execute();
    }

    /**
     * Main mock expects for case with update steps
     */
    private function mainExpectsForUpdate(): void
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
        $this->flagManagerMock->expects($this->once())
            ->method('delete')
            ->with(FlagManager::FLAG_ENV_FILE_ABSENCE);
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
