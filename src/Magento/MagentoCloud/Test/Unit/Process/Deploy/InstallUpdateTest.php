<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Unit\Process\Deploy;

use Magento\MagentoCloud\Process\Deploy\InstallUpdate;
use PHPUnit\Framework\TestCase;
use PHPUnit_Framework_MockObject_MockObject as Mock;
use Magento\MagentoCloud\Config\Deploy as DeployConfig;
use Psr\Log\LoggerInterface;

class InstallUpdateTest extends TestCase
{
    /**
     * @var LoggerInterface|Mock
     */
    private $loggerMock;

    /**
     * @var DeployConfig|Mock
     */
    private $deployConfigMock;

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
        $this->deployConfigMock = $this->createMock(DeployConfig::class);
        $this->loggerMock = $this->getMockForAbstractClass(LoggerInterface::class);

        $this->process = new InstallUpdate(
            $this->loggerMock,
            $this->deployConfigMock,
            $this->installProcessMock,
            $this->updateProcessMock
        );
    }

    public function testExecuteInstall()
    {
        $this->deployConfigMock->expects($this->once())
            ->method('isInstalling')
            ->willReturn(true);
        $this->loggerMock->expects($this->once())
            ->method('info')
            ->with('Starting install.');
        $this->installProcessMock->expects($this->once())
            ->method('execute');
        $this->updateProcessMock->expects($this->never())
            ->method('execute');

        $this->process->execute();
    }

    public function testExecuteUpdate()
    {
        $this->deployConfigMock->expects($this->once())
            ->method('isInstalling')
            ->willReturn(false);
        $this->loggerMock->expects($this->once())
            ->method('info')
            ->with('Starting update.');
        $this->installProcessMock->expects($this->never())
            ->method('execute');
        $this->updateProcessMock->expects($this->once())
            ->method('execute');

        $this->process->execute();
    }
}
