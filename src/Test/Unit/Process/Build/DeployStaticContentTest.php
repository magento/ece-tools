<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Unit\Process\Build;

use Magento\MagentoCloud\Config\Validator\GlobalStage\ScdOnBuild;
use Magento\MagentoCloud\Config\Validator\Result;
use Magento\MagentoCloud\Filesystem\Flag\Manager as FlagManager;
use Magento\MagentoCloud\Process\Build\DeployStaticContent;
use Magento\MagentoCloud\Process\ProcessInterface;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
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
     * @var ProcessInterface|Mock
     */
    private $processMock;

    /**
     * @var FlagManager|Mock
     */
    private $flagManagerMock;

    /**
     * @var ScdOnBuild|Mock
     */
    private $scdOnBuildMock;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        $this->loggerMock = $this->getMockForAbstractClass(LoggerInterface::class);
        $this->processMock = $this->getMockForAbstractClass(ProcessInterface::class);
        $this->configFileStructureMock = $this->createMock(ConfigFileStructure::class);
        $this->flagManagerMock = $this->createMock(FlagManager::class);
        $this->scdOnBuildMock = $this->createMock(ScdOnBuild::class);

        $this->process = new DeployStaticContent(
            $this->loggerMock,
            $this->processMock,
            $this->flagManagerMock,
            $this->scdOnBuildMock
        );
    }

    public function testExecute()
    {
        $this->flagManagerMock->expects($this->once())
            ->method('delete')
            ->with(FlagManager::FLAG_STATIC_CONTENT_DEPLOY_IN_BUILD);
        $this->processMock->expects($this->once())
            ->method('execute');
        $this->flagManagerMock->expects($this->once())
            ->method('set')
            ->with(FlagManager::FLAG_STATIC_CONTENT_DEPLOY_IN_BUILD);
        $this->scdOnBuildMock->expects($this->once())
            ->method('validate')
            ->willReturn(new Result\Success());

        $this->process->execute();
    }

    public function testExecuteWithError()
    {
        $this->flagManagerMock->expects($this->once())
            ->method('delete')
            ->with(FlagManager::FLAG_STATIC_CONTENT_DEPLOY_IN_BUILD);
        $this->processMock->expects($this->never())
            ->method('execute');
        $this->flagManagerMock->expects($this->never())
            ->method('set');
        $this->scdOnBuildMock->expects($this->once())
            ->method('validate')
            ->willReturn(new Result\Error('Some error'));
        $this->loggerMock->expects($this->once())
            ->method('notice')
            ->with('Skipping static content deploy: Some error');

        $this->process->execute();
    }
}
