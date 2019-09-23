<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Test\Unit\Process\Deploy;

use Magento\MagentoCloud\Config\GlobalSection as GlobalConfig;
use Magento\MagentoCloud\Config\Stage\DeployInterface;
use Magento\MagentoCloud\Filesystem\Flag\Manager as FlagManager;
use Magento\MagentoCloud\Process\ProcessException;
use Magento\MagentoCloud\Process\ProcessInterface;
use Magento\MagentoCloud\Shell\ShellInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Magento\MagentoCloud\Process\Deploy\DeployStaticContent;
use Magento\MagentoCloud\Util\StaticContentCleaner;
use Psr\Log\LoggerInterface;

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
     * @var FlagManager|MockObject
     */
    private $flagManagerMock;

    /**
     * @var ShellInterface|MockObject
     */
    private $shellMock;

    /**
     * @var LoggerInterface|MockObject
     */
    private $loggerMock;

    /**
     * @var ProcessInterface|MockObject
     */
    private $processMock;

    /**
     * @var DeployInterface|MockObject
     */
    private $stageConfigMock;

    /**
     * @var GlobalConfig|MockObject
     */
    private $globalConfigMock;

    /**
     * @var StaticContentCleaner|MockObject
     */
    private $staticContentCleanerMock;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        $this->flagManagerMock = $this->createMock(FlagManager::class);
        $this->shellMock = $this->getMockForAbstractClass(ShellInterface::class);
        $this->loggerMock = $this->getMockForAbstractClass(LoggerInterface::class);
        $this->processMock = $this->getMockForAbstractClass(ProcessInterface::class);
        $this->stageConfigMock = $this->getMockForAbstractClass(DeployInterface::class);
        $this->globalConfigMock = $this->createMock(GlobalConfig::class);
        $this->staticContentCleanerMock = $this->createMock(StaticContentCleaner::class);

        $this->process = new DeployStaticContent(
            $this->flagManagerMock,
            $this->loggerMock,
            $this->stageConfigMock,
            $this->globalConfigMock,
            $this->staticContentCleanerMock,
            [$this->processMock]
        );
    }

    /**
     * @throws ProcessException
     */
    public function testExecuteOnRemoteInDeploy()
    {
        $this->globalConfigMock->expects($this->once())
            ->method('get')
            ->with(GlobalConfig::VAR_SCD_ON_DEMAND)
            ->willReturn(false);
        $this->flagManagerMock->expects($this->once())
            ->method('exists')
            ->with(FlagManager::FLAG_STATIC_CONTENT_DEPLOY_IN_BUILD)
            ->willReturn(false);
        $this->loggerMock->expects($this->exactly(2))
            ->method('notice')
            ->withConsecutive(
                ['Generating fresh static content'],
                ['End of generating fresh static content']
            );
        $this->stageConfigMock->method('get')
            ->willReturnMap([
                [DeployInterface::VAR_CLEAN_STATIC_FILES, true],
                [DeployInterface::VAR_SKIP_SCD, false],
            ]);
        $this->staticContentCleanerMock->expects($this->once())
            ->method('clean');
        $this->processMock->expects($this->once())
            ->method('execute');

        $this->process->execute();
    }

    /**
     * @throws ProcessException
     */
    public function testExecuteOnRemoteWithoutCleaning()
    {
        $this->globalConfigMock->expects($this->once())
            ->method('get')
            ->with(GlobalConfig::VAR_SCD_ON_DEMAND)
            ->willReturn(false);
        $this->flagManagerMock->expects($this->once())
            ->method('exists')
            ->with(FlagManager::FLAG_STATIC_CONTENT_DEPLOY_IN_BUILD)
            ->willReturn(false);
        $this->loggerMock->expects($this->exactly(2))
            ->method('notice')
            ->withConsecutive(
                ['Generating fresh static content'],
                ['End of generating fresh static content']
            );
        $this->stageConfigMock->expects($this->any())
            ->method('get')
            ->willReturnMap([
                [DeployInterface::VAR_CLEAN_STATIC_FILES, false],
                [DeployInterface::VAR_SKIP_SCD, false],
            ]);
        $this->staticContentCleanerMock->expects($this->never())
            ->method('clean');
        $this->processMock->expects($this->once())
            ->method('execute');

        $this->process->execute();
    }

    /**
     * @throws ProcessException
     */
    public function testExecuteOnRemoteDoNotDeploy()
    {
        $this->globalConfigMock->expects($this->once())
            ->method('get')
            ->with(GlobalConfig::VAR_SCD_ON_DEMAND)
            ->willReturn(false);
        $this->flagManagerMock->expects($this->once())
            ->method('exists')
            ->with(FlagManager::FLAG_STATIC_CONTENT_DEPLOY_IN_BUILD)
            ->willReturn(true);
        $this->staticContentCleanerMock->expects($this->never())
            ->method('clean');

        $this->process->execute();
    }

    /**
     * @throws ProcessException
     */
    public function testExecuteScdOnDemandInProduction()
    {
        $this->globalConfigMock->expects($this->once())
            ->method('get')
            ->with(GlobalConfig::VAR_SCD_ON_DEMAND)
            ->willReturn(true);
        $this->loggerMock->expects($this->once())
            ->method('notice')
            ->withConsecutive(
                ['Skipping static content deploy. SCD on demand is enabled.']
            );
        $this->flagManagerMock->expects($this->never())
            ->method('exists');
        $this->staticContentCleanerMock->expects($this->once())
            ->method('clean');

        $this->process->execute();
    }
}
