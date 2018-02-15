<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Unit\Process\Prestart;

use Magento\MagentoCloud\Config\Environment;
use Magento\MagentoCloud\Config\GlobalSection as GlobalConfig;
use Magento\MagentoCloud\Config\Stage\DeployInterface;
use Magento\MagentoCloud\Filesystem\Flag\Manager as FlagManager;
use Magento\MagentoCloud\Process\ProcessInterface;
use Magento\MagentoCloud\Util\RemoteDiskIdentifier;
use PHPUnit\Framework\TestCase;
use PHPUnit_Framework_MockObject_MockObject as Mock;
use Magento\MagentoCloud\Process\Prestart\DeployStaticContent;
use Psr\Log\LoggerInterface;
use Magento\MagentoCloud\Util\StaticContentCleaner;

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
     * @var Environment|Mock
     */
    private $environmentMock;

    /**
     * @var LoggerInterface|Mock
     */
    private $loggerMock;

    /**
     * @var RemoteDiskIdentifier|Mock
     */
    private $remoteDiskIdentifierMock;

    /**
     * @var FlagManager|Mock
     */
    private $flagManagerMock;

    /**
     * @var ProcessInterface|Mock
     */
    private $processMock;

    /**
     * @var DeployInterface|Mock
     */
    private $stageConfigMock;

    /**
     * @var GlobalConfig|Mock
     */
    private $globalConfigMock;

    /**
     * @var StaticContentCleaner|Mock
     */
    private $staticContentCleanerMock;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        $this->environmentMock = $this->createMock(Environment::class);
        $this->loggerMock = $this->getMockForAbstractClass(LoggerInterface::class);
        $this->remoteDiskIdentifierMock = $this->createMock(RemoteDiskIdentifier::class);
        $this->processMock = $this->getMockForAbstractClass(ProcessInterface::class);
        $this->stageConfigMock = $this->getMockForAbstractClass(DeployInterface::class);
        $this->flagManagerMock = $this->createMock(FlagManager::class);
        $this->globalConfigMock = $this->createMock(GlobalConfig::class);
        $this->staticContentCleanerMock = $this->createMock(StaticContentCleaner::class);

        $this->process = new DeployStaticContent(
            $this->processMock,
            $this->environmentMock,
            $this->loggerMock,
            $this->remoteDiskIdentifierMock,
            $this->flagManagerMock,
            $this->stageConfigMock,
            $this->globalConfigMock,
            $this->staticContentCleanerMock
        );
    }

    public function testExecuteOnRemote()
    {
        $this->globalConfigMock->expects($this->once())
            ->method('get')
            ->with(GlobalConfig::VAR_SCD_ON_DEMAND_IN_PRODUCTION)
            ->willReturn(false);
        $this->remoteDiskIdentifierMock->expects($this->once())
            ->method('isOnLocalDisk')
            ->with('pub/static')
            ->willReturn(false);
        $this->flagManagerMock->expects($this->never())
            ->method('exists');
        $this->environmentMock->expects($this->never())
            ->method('isDeployStaticContent');
        $this->staticContentCleanerMock->expects($this->never())
            ->method('clean');

        $this->process->execute();
    }

    public function testExecuteOnLocalWithoutCleaning()
    {
        $this->globalConfigMock->expects($this->once())
            ->method('get')
            ->with(GlobalConfig::VAR_SCD_ON_DEMAND_IN_PRODUCTION)
            ->willReturn(false);
        $this->remoteDiskIdentifierMock->expects($this->once())
            ->method('isOnLocalDisk')
            ->with('pub/static')
            ->willReturn(true);
        $this->flagManagerMock->expects($this->once())
            ->method('exists')
            ->with(FlagManager::FLAG_STATIC_CONTENT_DEPLOY_PENDING)
            ->willReturn(true);
        $this->environmentMock->expects($this->once())
            ->method('isDeployStaticContent')
            ->willReturn(true);
        $this->loggerMock->expects($this->once())
            ->method('info')
            ->with('Generating fresh static content');
        $this->stageConfigMock->expects($this->exactly(2))
            ->method('get')
            ->willReturnMap([
                [DeployInterface::VAR_CLEAN_STATIC_FILES, false],
                [DeployInterface::VAR_SKIP_SCD, false]
            ]);
        $this->staticContentCleanerMock->expects($this->never())
            ->method('clean');

        $this->processMock->expects($this->once())
            ->method('execute');

        $this->process->execute();
    }

    public function testExecuteOnLocalDoNotDeploy()
    {
        $this->globalConfigMock->expects($this->once())
            ->method('get')
            ->with(GlobalConfig::VAR_SCD_ON_DEMAND_IN_PRODUCTION)
            ->willReturn(false);
        $this->remoteDiskIdentifierMock->expects($this->once())
            ->method('isOnLocalDisk')
            ->with('pub/static')
            ->willReturn(true);
        $this->flagManagerMock->expects($this->once())
            ->method('exists')
            ->with(FlagManager::FLAG_STATIC_CONTENT_DEPLOY_PENDING)
            ->willReturn(true);
        $this->environmentMock->expects($this->once())
            ->method('isDeployStaticContent')
            ->willReturn(false);
        $this->staticContentCleanerMock->expects($this->never())
            ->method('clean');
        $this->loggerMock->expects($this->never())
            ->method('info');

        $this->process->execute();
    }

    public function testDoCleanStaticFiles()
    {
        $this->globalConfigMock->expects($this->once())
            ->method('get')
            ->with(GlobalConfig::VAR_SCD_ON_DEMAND_IN_PRODUCTION)
            ->willReturn(false);
        $this->remoteDiskIdentifierMock->expects($this->once())
            ->method('isOnLocalDisk')
            ->with('pub/static')
            ->willReturn(true);
        $this->flagManagerMock->expects($this->once())
            ->method('exists')
            ->with(FlagManager::FLAG_STATIC_CONTENT_DEPLOY_PENDING)
            ->willReturn(true);
        $this->environmentMock->expects($this->once())
            ->method('isDeployStaticContent')
            ->willReturn(true);
        $this->staticContentCleanerMock->expects($this->once())
            ->method('clean');
        $this->loggerMock->expects($this->once())
            ->method('info')
            ->with('Generating fresh static content');
        $this->stageConfigMock->expects($this->exactly(2))
            ->method('get')
            ->willReturnMap([
                [DeployInterface::VAR_CLEAN_STATIC_FILES, true],
                [DeployInterface::VAR_SKIP_SCD, false]
            ]);

        $this->processMock->expects($this->once())
            ->method('execute');

        $this->process->execute();
    }

    public function testExecuteScdOnDemandInProduction()
    {
        $this->globalConfigMock->expects($this->once())
            ->method('get')
            ->with(GlobalConfig::VAR_SCD_ON_DEMAND_IN_PRODUCTION)
            ->willReturn(true);
        $this->loggerMock->expects($this->once())
            ->method('notice')
            ->with('Skipping static content deploy. Enabled static content deploy on demand.');
        $this->loggerMock->expects($this->never())
            ->method('info');
        $this->remoteDiskIdentifierMock->expects($this->never())
            ->method('isOnLocalDisk');
        $this->flagManagerMock->expects($this->never())
            ->method('exists');
        $this->staticContentCleanerMock->expects($this->once())
            ->method('clean');
        $this->environmentMock->expects($this->never())
            ->method('isDeployStaticContent');

        $this->process->execute();
    }
}
