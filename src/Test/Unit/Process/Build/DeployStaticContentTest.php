<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Unit\Process\Build;

use Magento\MagentoCloud\Config\Environment;
use Magento\MagentoCloud\Config\StageConfigInterface;
use Magento\MagentoCloud\Config\Build;
use Magento\MagentoCloud\Config\Validator\Result;
use Magento\MagentoCloud\Filesystem\FlagFile\Flag\StaticContentDeployInBuild;
use Magento\MagentoCloud\Filesystem\FlagFile\Manager as FlagFileManager;
use Magento\MagentoCloud\Process\Build\DeployStaticContent;
use Magento\MagentoCloud\Process\ProcessInterface;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Magento\MagentoCloud\Config\Build as BuildConfig;
use Magento\MagentoCloud\Config\Validator\Build\ConfigFileStructure;
use PHPUnit_Framework_MockObject_MockObject as Mock;

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
     * @var LoggerInterface|Mock
     */
    private $loggerMock;

    /**
     * @var StageConfigInterface|Mock
     */
    private $stageConfigMock;

    /**
     * @var Environment|Mock
     */
    private $environmentMock;

    /**
     * @var BuildConfig|Mock
     */
    private $buildConfigMock;

    /**
     * @var ProcessInterface|Mock
     */
    private $processMock;

    /**
     * @var ConfigFileStructure|Mock
     */
    private $configFileStructureMock;

    /**
     * @var FlagFileManager|Mock
     */
    private $flagFileManagerMock;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        $this->loggerMock = $this->getMockBuilder(LoggerInterface::class)
            ->getMockForAbstractClass();
        $this->stageConfigMock = $this->getMockForAbstractClass(StageConfigInterface::class);
        $this->environmentMock = $this->createMock(Environment::class);
        $this->buildConfigMock = $this->createMock(Build::class);
        $this->processMock = $this->getMockForAbstractClass(ProcessInterface::class);
        $this->configFileStructureMock = $this->createMock(ConfigFileStructure::class);
        $this->flagFileManagerMock = $this->createMock(FlagFileManager::class);
        $this->flagFileManagerMock->expects($this->once())
            ->method('delete')
            ->with(StaticContentDeployInBuild::KEY);

        $this->process = new DeployStaticContent(
            $this->loggerMock,
            $this->stageConfigMock,
            $this->environmentMock,
            $this->buildConfigMock,
            $this->processMock,
            $this->configFileStructureMock,
            $this->flagFileManagerMock
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
        $this->flagFileManagerMock->expects($this->once())
            ->method('set')
            ->with(StaticContentDeployInBuild::KEY);

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
