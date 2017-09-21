<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Unit\Process\Deploy;

use Magento\MagentoCloud\Process\Deploy\PreDeploy;
use Magento\MagentoCloud\Process\ProcessInterface;
use Magento\MagentoCloud\Util\PackageManager;
use PHPUnit\Framework\TestCase;
use PHPUnit_Framework_MockObject_MockObject as Mock;
use Psr\Log\LoggerInterface;

class PreDeployTest extends TestCase
{
    /**
     * @var LoggerInterface|Mock
     */
    private $loggerMock;

    /**
     * @var PackageManager|Mock
     */
    private $packageManagerMock;

    /**
     * @var ProcessInterface|Mock
     */
    private $processMock;

    /**
     * @var PreDeploy
     */
    private $process;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        $this->loggerMock = $this->getMockBuilder(LoggerInterface::class)
            ->getMockForAbstractClass();
        $this->packageManagerMock = $this->createMock(PackageManager::class);
        $this->processMock = $this->getMockBuilder(ProcessInterface::class)
            ->getMockForAbstractClass();

        $this->process = new PreDeploy(
            $this->loggerMock,
            $this->processMock,
            $this->packageManagerMock
        );
    }

    public function testExecute()
    {
        $this->packageManagerMock->expects($this->once())
            ->method('get')
            ->willReturn('(components info)');
        $this->loggerMock->expects($this->once())
            ->method('info')
            ->with('Starting predeploy. (components info)');
        $this->processMock->expects($this->once())
            ->method('execute');

        $this->process->execute();
    }
}
