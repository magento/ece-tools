<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Unit\Process\Deploy;

use Magento\MagentoCloud\Process\Deploy\PreDeploy;
use Magento\MagentoCloud\Process\ProcessInterface;
use Magento\MagentoCloud\Util\MaintenanceModeSwitcher;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * @inheritdoc
 */
class PreDeployTest extends TestCase
{
    /**
     * @var LoggerInterface|MockObject
     */
    private $loggerMock;

    /**
     * @var ProcessInterface|MockObject
     */
    private $processMock;

    /**
     * @var MaintenanceModeSwitcher|MockObject
     */
    private $maintenanceModeSwitcher;

    /**
     * @var PreDeploy
     */
    private $process;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        $this->loggerMock = $this->getMockForAbstractClass(LoggerInterface::class);
        $this->processMock = $this->getMockForAbstractClass(ProcessInterface::class);
        $this->maintenanceModeSwitcher = $this->createMock(MaintenanceModeSwitcher::class);

        $this->process = new PreDeploy(
            $this->loggerMock,
            $this->processMock,
            $this->maintenanceModeSwitcher
        );
    }

    public function testExecute()
    {
        $this->loggerMock->expects($this->exactly(2))
            ->method('notice')
            ->withConsecutive(
                ['Starting pre-deploy.'],
                ['End of pre-deploy.']
            );
        $this->maintenanceModeSwitcher->expects($this->once())
            ->method('enable');
        $this->processMock->expects($this->once())
            ->method('execute');

        $this->process->execute();
    }

    /**
     * @expectedException \Magento\MagentoCloud\Process\ProcessException
     * @expectedExceptionMessage some error
     */
    public function testExecuteWithMaintenanceModeException()
    {
        $this->loggerMock->expects($this->exactly(1))
            ->method('notice')
            ->with('Starting pre-deploy.');
        $this->maintenanceModeSwitcher->expects($this->once())
            ->method('enable')
            ->willThrowException(new \RuntimeException('some error'));

        $this->process->execute();
    }
}
