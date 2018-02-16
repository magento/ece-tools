<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Unit\Process\Deploy;

use Magento\MagentoCloud\Config\Environment;
use Magento\MagentoCloud\Config\GlobalSection as GlobalConfig;
use Magento\MagentoCloud\Config\Stage\DeployInterface;
use Magento\MagentoCloud\Filesystem\Flag\Manager as FlagManager;
use Magento\MagentoCloud\Process\ProcessInterface;
use Magento\MagentoCloud\Shell\ShellInterface;
use Magento\MagentoCloud\Util\RemoteDiskIdentifier;
use PHPUnit\Framework\TestCase;
use PHPUnit_Framework_MockObject_MockObject as Mock;
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
     * @var Environment|Mock
     */
    private $environmentMock;

    /**
     * @var ShellInterface|Mock
     */
    private $shellMock;

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
        $this->shellMock = $this->getMockForAbstractClass(ShellInterface::class);
        $this->loggerMock = $this->getMockForAbstractClass(LoggerInterface::class);
        $this->remoteDiskIdentifierMock = $this->createMock(RemoteDiskIdentifier::class);
        $this->processMock = $this->getMockForAbstractClass(ProcessInterface::class);
        $this->stageConfigMock = $this->getMockForAbstractClass(DeployInterface::class);
        $this->globalConfigMock = $this->createMock(GlobalConfig::class);
        $this->staticContentCleanerMock = $this->createMock(StaticContentCleaner::class);
        $this->flagManagerMock = $this->createMock(FlagManager::class);
        $this->flagManagerMock->expects($this->once())
            ->method('delete')
            ->with(FlagManager::FLAG_STATIC_CONTENT_DEPLOY_PENDING);

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

    public function testExecuteOnRemoteInDeploy()
    {
        $this->globalConfigMock->expects($this->once())
            ->method('get')
            ->with(GlobalConfig::VAR_SCD_ON_DEMAND)
            ->willReturn(false);
        $this->remoteDiskIdentifierMock->expects($this->once())
            ->method('isOnLocalDisk')
            ->with('pub/static')
            ->willReturn(false);
        $this->environmentMock->expects($this->once())
            ->method('isDeployStaticContent')
            ->willReturn(true);
        $this->loggerMock->expects($this->once())
            ->method('info')
            ->with('Generating fresh static content');
        $this->stageConfigMock->expects($this->any())
            ->method('get')
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

    public function testExecuteOnRemoteWithoutCleaning()
    {
        $this->globalConfigMock->expects($this->once())
            ->method('get')
            ->with(GlobalConfig::VAR_SCD_ON_DEMAND)
            ->willReturn(false);
        $this->remoteDiskIdentifierMock->expects($this->once())
            ->method('isOnLocalDisk')
            ->with('pub/static')
            ->willReturn(false);
        $this->flagManagerMock->expects($this->never())
            ->method('set');
        $this->flagManagerMock->expects($this->never())
            ->method('exists');
        $this->environmentMock->expects($this->once())
            ->method('isDeployStaticContent')
            ->willReturn(true);
        $this->loggerMock->expects($this->once())
            ->method('info')
            ->withConsecutive(
                ['Generating fresh static content']
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

    public function testExecuteOnRemoteDoNotDeploy()
    {
        $this->globalConfigMock->expects($this->once())
            ->method('get')
            ->with(GlobalConfig::VAR_SCD_ON_DEMAND)
            ->willReturn(false);
        $this->remoteDiskIdentifierMock->expects($this->once())
            ->method('isOnLocalDisk')
            ->with('pub/static')
            ->willReturn(false);
        $this->flagManagerMock->expects($this->never())
            ->method('set');
        $this->flagManagerMock->expects($this->never())
            ->method('exists');
        $this->environmentMock->expects($this->once())
            ->method('isDeployStaticContent')
            ->willReturn(false);
        $this->staticContentCleanerMock->expects($this->never())
            ->method('clean');

        $this->process->execute();
    }

    public function testExecuteOnLocal()
    {
        $this->globalConfigMock->expects($this->once())
            ->method('get')
            ->with(GlobalConfig::VAR_SCD_ON_DEMAND)
            ->willReturn(false);
        $this->remoteDiskIdentifierMock->expects($this->once())
            ->method('isOnLocalDisk')
            ->with('pub/static')
            ->willReturn(true);
        $this->flagManagerMock->expects($this->once())
            ->method('exists')
            ->with(FlagManager::FLAG_STATIC_CONTENT_DEPLOY_IN_BUILD)
            ->willReturn(false);
        $this->flagManagerMock->expects($this->once())
            ->method('set')
            ->with(FlagManager::FLAG_STATIC_CONTENT_DEPLOY_PENDING)
            ->willReturn(false);
        $this->loggerMock->expects($this->once())
            ->method('info')
            ->with('Postpone static content deployment until prestart');
        $this->environmentMock->expects($this->never())
            ->method('isDeployStaticContent');
        $this->staticContentCleanerMock->expects($this->never())
            ->method('clean');

        $this->process->execute();
    }

    public function testExecuteScdOnDemandInProduction()
    {
        $this->globalConfigMock->expects($this->once())
            ->method('get')
            ->with(GlobalConfig::VAR_SCD_ON_DEMAND)
            ->willReturn(true);
        $this->loggerMock->expects($this->once())
            ->method('notice')
            ->with('Skipping static content deploy. SCD on demand is enabled.');
        $this->loggerMock->expects($this->never())
            ->method('info');
        $this->remoteDiskIdentifierMock->expects($this->never())
            ->method('isOnLocalDisk');
        $this->flagManagerMock->expects($this->never())
            ->method('exists');
        $this->flagManagerMock->expects($this->never())
            ->method('set');
        $this->environmentMock->expects($this->never())
            ->method('isDeployStaticContent');
        $this->staticContentCleanerMock->expects($this->once())
            ->method('clean');

        $this->process->execute();
    }
}
