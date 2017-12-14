<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Unit\Process\Deploy;

use Magento\MagentoCloud\Config\Environment;
use Magento\MagentoCloud\Filesystem\DirectoryList;
use Magento\MagentoCloud\Filesystem\Driver\File;
use Magento\MagentoCloud\Filesystem\FlagFileInterface;
use Magento\MagentoCloud\Filesystem\FlagFilePool;
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
     * @var FlagFilePool|Mock
     */
    private $flagFilePoolMock;

    /**
     * @var FlagFileInterface|Mock
     */
    private $scdInBuildFlagMock;

    /**
     * @var FlagFileInterface|Mock
     */
    private $scdPendingFlagMock;

    /**
     * @var ProcessInterface|Mock
     */
    private $processMock;

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
        $this->flagFilePoolMock = $this->createMock(FlagFilePool::class);
        $this->scdInBuildFlagMock = $this->getMockBuilder(FlagFileInterface::class)
            ->getMockForAbstractClass();
        $this->scdPendingFlagMock = $this->getMockBuilder(FlagFileInterface::class)
            ->getMockForAbstractClass();

        $this->flagFilePoolMock->expects($this->exactly(2))
            ->method('getFlag')
            ->willReturnMap([
                ['scd_in_build', $this->scdInBuildFlagMock],
                ['scd_pending', $this->scdPendingFlagMock],
            ]);
        $this->scdPendingFlagMock->expects($this->once())
            ->method('delete');

        $this->process = new DeployStaticContent(
            $this->processMock,
            $this->environmentMock,
            $this->loggerMock,
            $this->fileMock,
            $this->directoryListMock,
            $this->remoteDiskIdentifierMock,
            $this->flagFilePoolMock
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
        $this->environmentMock->expects($this->once())
            ->method('doCleanStaticFiles')
            ->willReturn(true);
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
        $this->environmentMock->expects($this->once())
            ->method('doCleanStaticFiles')
            ->willReturn(false);
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
        $this->environmentMock->expects($this->once())
            ->method('getApplicationMode')
            ->willReturn('Developer');
        $this->environmentMock->expects($this->never())
            ->method('isDeployStaticContent');
        $this->loggerMock->expects($this->once())
            ->method('info')
            ->with('Application mode is Developer');
        $this->environmentMock->expects($this->never())
            ->method('doCleanStaticFiles');
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
        $this->environmentMock->expects($this->never())
            ->method('doCleanStaticFiles');
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
        $this->scdInBuildFlagMock->expects($this->once())
            ->method('exists')
            ->willReturn(false);
        $this->scdPendingFlagMock->expects($this->once())
            ->method('set');
        $this->loggerMock->expects($this->once())
            ->method('info')
            ->with('Postpone static content deployment until prestart');

        $this->environmentMock->expects($this->never())
            ->method('getApplicationMode');
        $this->environmentMock->expects($this->never())
            ->method('isDeployStaticContent');
        $this->environmentMock->expects($this->never())
            ->method('doCleanStaticFiles');
        $this->fileMock->expects($this->never())
            ->method('backgroundClearDirectory');

        $this->process->execute();
    }
}
