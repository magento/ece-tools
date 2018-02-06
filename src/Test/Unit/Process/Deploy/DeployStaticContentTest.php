<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Unit\Process\Deploy;

use Magento\MagentoCloud\Config\Environment;
use Magento\MagentoCloud\Config\GlobalSection as GlobalConfig;
use Magento\MagentoCloud\Config\Stage\DeployInterface;
use Magento\MagentoCloud\Filesystem\DirectoryList;
use Magento\MagentoCloud\Filesystem\Driver\File;
use Magento\MagentoCloud\Filesystem\Flag\Manager as FlagManager;
use Magento\MagentoCloud\Process\ProcessInterface;
use Magento\MagentoCloud\Shell\ShellInterface;
use Magento\MagentoCloud\Util\RemoteDiskIdentifier;
use PHPUnit\Framework\TestCase;
use PHPUnit_Framework_MockObject_MockObject as Mock;
use Magento\MagentoCloud\Process\Deploy\DeployStaticContent;
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
     * @var File|Mock
     */
    private $fileMock;

    /**
     * @var DirectoryList|Mock
     */
    private $directoryListMock;

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
     * @inheritdoc
     */
    protected function setUp()
    {
        $this->environmentMock = $this->createMock(Environment::class);
        $this->shellMock = $this->getMockBuilder(ShellInterface::class)
            ->getMockForAbstractClass();
        $this->loggerMock = $this->getMockBuilder(LoggerInterface::class)
            ->getMockForAbstractClass();
        $this->fileMock = $this->createMock(File::class);
        $this->directoryListMock = $this->createMock(DirectoryList::class);
        $this->remoteDiskIdentifierMock = $this->createMock(RemoteDiskIdentifier::class);
        $this->processMock = $this->getMockBuilder(ProcessInterface::class)
            ->getMockForAbstractClass();
        $this->flagManagerMock = $this->createMock(FlagManager::class);
        $this->flagManagerMock->expects($this->once())
            ->method('delete')
            ->with(FlagManager::FLAG_STATIC_CONTENT_DEPLOY_PENDING);
        $this->stageConfigMock = $this->getMockForAbstractClass(DeployInterface::class);
        $this->globalConfigMock = $this->createMock(GlobalConfig::class);

        $this->process = new DeployStaticContent(
            $this->processMock,
            $this->environmentMock,
            $this->loggerMock,
            $this->fileMock,
            $this->directoryListMock,
            $this->remoteDiskIdentifierMock,
            $this->flagManagerMock,
            $this->stageConfigMock,
            $this->globalConfigMock
        );
    }

    public function testExecuteOnRemoteInDeploy()
    {
        $this->globalConfigMock->expects($this->once())
            ->method('get')
            ->with(GlobalConfig::VAR_SCD_ON_DEMAND_IN_PRODUCTION)
            ->willReturn(false);
        $this->remoteDiskIdentifierMock->expects($this->once())
            ->method('isOnLocalDisk')
            ->with('pub/static')
            ->willReturn(false);
        $this->environmentMock->expects($this->once())
            ->method('isDeployStaticContent')
            ->willReturn(true);
        $this->loggerMock->expects($this->exactly(3))
            ->method('info')
            ->withConsecutive(
                ['Clearing pub/static'],
                ['Clearing var/view_preprocessed'],
                ['Generating fresh static content']
            );
        $this->stageConfigMock->expects($this->any())
            ->method('get')
            ->willReturnMap([
                [DeployInterface::VAR_CLEAN_STATIC_FILES, true],
                [DeployInterface::VAR_SKIP_SCD, false],
            ]);
        $this->directoryListMock->expects($this->once())
            ->method('getMagentoRoot')
            ->willReturn('magento_root');
        $this->fileMock->expects($this->exactly(2))
            ->method('backgroundClearDirectory')
            ->withConsecutive(
                ['magento_root/pub/static'],
                ['magento_root/var/view_preprocessed']
            );
        $this->processMock->expects($this->once())
            ->method('execute');

        $this->process->execute();
    }

    public function testExecuteOnRemoteWithoutCleaning()
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
        $this->fileMock->expects($this->never())
            ->method('backgroundClearDirectory');
        $this->processMock->expects($this->once())
            ->method('execute');

        $this->process->execute();
    }

    public function testExecuteOnRemoteDoNotDeploy()
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
            ->method('set');
        $this->flagManagerMock->expects($this->never())
            ->method('exists');
        $this->environmentMock->expects($this->once())
            ->method('isDeployStaticContent')
            ->willReturn(false);
        $this->fileMock->expects($this->never())
            ->method('backgroundClearDirectory');

        $this->process->execute();
    }

    public function testExecuteOnLocal()
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
        $this->fileMock->expects($this->never())
            ->method('backgroundClearDirectory');

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
        $this->remoteDiskIdentifierMock->expects($this->never())
            ->method('isOnLocalDisk');
        $this->flagManagerMock->expects($this->never())
            ->method('exists');
        $this->flagManagerMock->expects($this->never())
            ->method('set');
        $this->environmentMock->expects($this->never())
            ->method('isDeployStaticContent');
        $this->fileMock->expects($this->never())
            ->method('backgroundClearDirectory');

        $this->process->execute();
    }
}
