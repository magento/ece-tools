<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Unit\Process\Build;

use Magento\MagentoCloud\Config\Environment;
use Magento\MagentoCloud\Config\StageConfigInterface;
use Magento\MagentoCloud\Config\Validator\Result;
use Magento\MagentoCloud\Process\Build\DeployStaticContent;
use Magento\MagentoCloud\Process\ProcessInterface;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Magento\MagentoCloud\Config\Validator\Build\ConfigFileStructure;

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
     * @var StageConfigInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $stageConfigMock;

    /**
     * @var Environment|\PHPUnit_Framework_MockObject_MockObject
     */
    private $environmentMock;

    /**
     * @var ProcessInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $processMock;

    /**
     * @var ConfigFileStructure|\PHPUnit_Framework_MockObject_MockObject
     */
    private $configFileStructureMock;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        $this->loggerMock = $this->getMockBuilder(LoggerInterface::class)
            ->getMockForAbstractClass();
        $this->stageConfigMock = $this->getMockForAbstractClass(StageConfigInterface::class);
        $this->environmentMock = $this->createMock(Environment::class);
        $this->processMock = $this->getMockForAbstractClass(ProcessInterface::class);
        $this->configFileStructureMock = $this->createMock(ConfigFileStructure::class);

        $this->environmentMock->expects($this->once())
            ->method('removeFlagStaticContentInBuild');

        $this->process = new DeployStaticContent(
            $this->loggerMock,
            $this->stageConfigMock,
            $this->environmentMock,
            $this->processMock,
            $this->configFileStructureMock
        );
    }

    public function testExecute()
    {
        $this->stageConfigMock->expects($this->once())
            ->method('get')
            ->with(StageConfigInterface::VAR_SKIP_SCD)
            ->willReturn(false);
        $resultMock = $this->createMock(Result\Success::class);
        $this->configFileStructureMock->expects($this->once())
            ->method('validate')
            ->willReturn($resultMock);
        $this->processMock->expects($this->once())
            ->method('execute');

        $this->process->execute();
    }

    public function testExecuteWithNotValidConfig()
    {
        $resultMock = $this->createMock(Result\Error::class);
        $resultMock->expects($this->once())
            ->method('getError')
            ->willReturn('error');
        $this->configFileStructureMock->expects($this->once())
            ->method('validate')
            ->willReturn($resultMock);
        $this->stageConfigMock->expects($this->once())
            ->method('get')
            ->with(StageConfigInterface::VAR_SKIP_SCD)
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
        $this->stageConfigMock->expects($this->once())
            ->method('get')
            ->with(StageConfigInterface::VAR_SKIP_SCD)
            ->willReturn(true);
        $this->configFileStructureMock->expects($this->never())
            ->method('validate');
        $this->processMock->expects($this->never())
            ->method('execute');

        $this->process->execute();
    }
}
