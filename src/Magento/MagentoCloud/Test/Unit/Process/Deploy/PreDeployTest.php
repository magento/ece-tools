<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Unit\Process\Deploy;

use Magento\MagentoCloud\Process\Deploy\PreDeploy;
use Magento\MagentoCloud\Process\ProcessInterface;
use Magento\MagentoCloud\Util\ComponentInfo;
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
     * @var ComponentInfo|Mock
     */
    private $componentInfoMock;

    /**
     * @var ProcessInterface|Mock
     */
    private $processMock;

    /**
     * @var PreDeploy
     */
    private $process;

    protected function setUp()
    {
        $this->loggerMock = $this->getMockBuilder(LoggerInterface::class)
            ->getMockForAbstractClass();
        $this->componentInfoMock = $this->createMock(ComponentInfo::class);
        $this->processMock = $this->getMockBuilder(ProcessInterface::class)
            ->getMockForAbstractClass();

        $this->process = new PreDeploy(
            $this->loggerMock,
            $this->processMock,
            $this->componentInfoMock
        );
    }

    public function testExecute()
    {
        $this->componentInfoMock->expects($this->once())
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
