<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Unit\Process\Deploy;

use Magento\MagentoCloud\Config\Environment;
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

        $this->process = new DeployStaticContent(
            $this->processMock,
            $this->environmentMock,
            $this->loggerMock,
            $this->fileMock,
            $this->directoryListMock,
            $this->remoteDiskIdentifierMock,
            $this->flagManagerMock,
            $this->stageConfigMock
        );
    }

    public function testExecuteOnRemoteInDeploy()
    {
        $this->remoteDiskIdentifierMock->expects($this->once())
            ->method('isOnLocalDisk')
            ->with('pub/static')
            ->willReturn(false);
        $this->environmentMock->expects($this->once())
            ->method('getApplicationMode')
            ->willReturn(Environment::MAGENTO_PRODUCTION_MODE);
        $this->environmentMock->expects($this->once())
            ->method('isDeployStaticContent')
            ->willReturn(true);
        $this->loggerMock->expects($this->exactly(4))
            ->method('info')
            ->withConsecutive(
                ['Application mode is ' . Environment::MAGENTO_PRODUCTION_MODE],
                ['Clearing pub/static'],
                ['Clearing var/view_preprocessed'],
                ['Generating fresh static content']
            );
        $this->stageConfigMock->expects($this->any())
            ->method('get')
            ->willReturnMap([
                [DeployInterface::VAR_CLEAN_STATIC_FILES, true],
                [DeployInterface::VAR_SKIP_SCD, false]
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
        $this->remoteDiskIdentifierMock->expects($this->once())
            ->method('isOnLocalDisk')
            ->with('pub/static')
            ->willReturn(false);
        $this->flagManagerMock->expects($this->never())
            ->method('set');
        $this->flagManagerMock->expects($this->never())
            ->method('exists');
        $this->environmentMock->expects($this->once())
            ->method('getApplicationMode')
            ->willReturn(Environment::MAGENTO_PRODUCTION_MODE);
        $this->environmentMock->expects($this->once())
            ->method('isDeployStaticContent')
            ->willReturn(true);
        $this->loggerMock->expects($this->exactly(2))
            ->method('info')
            ->withConsecutive(
                ['Application mode is ' . Environment::MAGENTO_PRODUCTION_MODE],
                ['Generating fresh static content']
            );
        $this->stageConfigMock->expects($this->any())
            ->method('get')
            ->willReturnMap([
                [DeployInterface::VAR_CLEAN_STATIC_FILES, false],
                [DeployInterface::VAR_SKIP_SCD, false]
            ]);
        $this->fileMock->expects($this->never())
            ->method('backgroundClearDirectory');
        $this->processMock->expects($this->once())
            ->method('execute');

        $this->process->execute();
    }

    public function testExecuteOnRemoteNonProductionMode()
    {
        $this->remoteDiskIdentifierMock->expects($this->once())
            ->method('isOnLocalDisk')
            ->with('pub/static')
            ->willReturn(false);
        $this->flagManagerMock->expects($this->never())
            ->method('set');
        $this->flagManagerMock->expects($this->never())
            ->method('exists');
        $this->environmentMock->expects($this->once())
            ->method('getApplicationMode')
            ->willReturn('Developer');
        $this->environmentMock->expects($this->never())
            ->method('isDeployStaticContent');
        $this->loggerMock->expects($this->once())
            ->method('info')
            ->with('Application mode is Developer');
        $this->fileMock->expects($this->never())
            ->method('backgroundClearDirectory');

        $this->process->execute();
    }

    public function testExecuteOnRemoteDoNotDeploy()
    {
        $this->remoteDiskIdentifierMock->expects($this->once())
            ->method('isOnLocalDisk')
            ->with('pub/static')
            ->willReturn(false);
        $this->flagManagerMock->expects($this->never())
            ->method('set');
        $this->flagManagerMock->expects($this->never())
            ->method('exists');
        $this->environmentMock->expects($this->once())
            ->method('getApplicationMode')
            ->willReturn(Environment::MAGENTO_PRODUCTION_MODE);
        $this->environmentMock->expects($this->once())
            ->method('isDeployStaticContent')
            ->willReturn(false);
        $this->loggerMock->expects($this->once())
            ->method('info')
            ->withConsecutive(
                ['Application mode is ' . Environment::MAGENTO_PRODUCTION_MODE]
            );
        $this->fileMock->expects($this->never())
            ->method('backgroundClearDirectory');

        $this->process->execute();
    }

    public function testExecuteOnLocal()
    {
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
            ->method('getApplicationMode');
        $this->environmentMock->expects($this->never())
            ->method('isDeployStaticContent');
        $this->fileMock->expects($this->never())
            ->method('backgroundClearDirectory');

        $this->process->execute();
    }
}
