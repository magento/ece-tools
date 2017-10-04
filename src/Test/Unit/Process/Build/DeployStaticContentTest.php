<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Unit\Process\Build;

use Magento\MagentoCloud\Config\Build;
use Magento\MagentoCloud\Config\Environment;
use Magento\MagentoCloud\Config\Validator\Result;
use Magento\MagentoCloud\Process\Build\DeployStaticContent;
use Magento\MagentoCloud\Process\ProcessInterface;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Magento\MagentoCloud\Config\Validator\Build\ConfigFileScd;

/**
 * @inheritdoc
 */
class DeployStaticContentTest extends TestCase
{
    /**
     * @var DeployStaticContent
     */
    private $process;

    /**
     * @var LoggerInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $loggerMock;

    /**
     * @var Build|\PHPUnit_Framework_MockObject_MockObject
     */
    private $buildConfigMock;

    /**
     * @var Environment|\PHPUnit_Framework_MockObject_MockObject
     */
    private $environmentMock;

    /**
     * @var ProcessInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $processMock;

    /**
     * @var ConfigFileScd|\PHPUnit_Framework_MockObject_MockObject
     */
    private $configFileScdMock;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        $this->loggerMock = $this->getMockBuilder(LoggerInterface::class)
            ->getMockForAbstractClass();
        $this->buildConfigMock = $this->createMock(Build::class);
        $this->environmentMock = $this->createMock(Environment::class);
        $this->processMock = $this->getMockForAbstractClass(ProcessInterface::class);
        $this->configFileScdMock = $this->createMock(ConfigFileScd::class);

        $this->environmentMock->expects($this->once())
            ->method('removeFlagStaticContentInBuild');

        $this->process = new DeployStaticContent(
            $this->loggerMock,
            $this->buildConfigMock,
            $this->environmentMock,
            $this->processMock,
            $this->configFileScdMock
        );
    }


    public function testExecute()
    {
        $this->buildConfigMock->expects($this->once())
            ->method('get')
            ->with(Build::OPT_SKIP_SCD)
            ->willReturn(false);
        $resultMock = $this->createMock(Result::class);
        $resultMock->expects($this->once())
            ->method('hasErrors')
            ->willReturn(false);
        $this->configFileScdMock->expects($this->once())
            ->method('run')
            ->willReturn($resultMock);
        $this->processMock->expects($this->once())
            ->method('execute');

        $this->process->execute();
    }

    public function testExecuteWithNotValidConfig()
    {
        $resultMock = $this->createMock(Result::class);
        $resultMock->expects($this->once())
            ->method('hasErrors')
            ->willReturn(true);
        $resultMock->expects($this->once())
            ->method('getErrors')
            ->willReturn(['error']);
        $this->configFileScdMock->expects($this->once())
            ->method('run')
            ->willReturn($resultMock);
        $this->buildConfigMock->expects($this->once())
            ->method('get')
            ->with(Build::OPT_SKIP_SCD)
            ->willReturn(false);
        $this->loggerMock->expects($this->once())
            ->method('info')
            ->with('Skipping static content deploy. error');
        $this->processMock->expects($this->never())
            ->method('execute');

        $this->process->execute();
    }

    public function testExecuteSkipBuildOption()
    {
        $this->buildConfigMock->expects($this->once())
            ->method('get')
            ->with(Build::OPT_SKIP_SCD)
            ->willReturn(true);
        $this->configFileScdMock->expects($this->never())
            ->method('run');
        $this->processMock->expects($this->never())
            ->method('execute');

        $this->process->execute();
    }
}
