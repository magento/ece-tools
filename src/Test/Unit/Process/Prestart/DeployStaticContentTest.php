<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Unit\Process\Prestart;

use Magento\MagentoCloud\Config\Environment;
use Magento\MagentoCloud\Config\Stage\DeployInterface;
use Magento\MagentoCloud\Filesystem\DirectoryList;
use Magento\MagentoCloud\Filesystem\Driver\File;
use Magento\MagentoCloud\Filesystem\FlagFileInterface;
use Magento\MagentoCloud\Filesystem\FlagFilePool;
use Magento\MagentoCloud\Process\ProcessInterface;
use Magento\MagentoCloud\Util\RemoteDiskIdentifier;
use PHPUnit\Framework\TestCase;
use PHPUnit_Framework_MockObject_MockObject as Mock;
use Magento\MagentoCloud\Process\Prestart\DeployStaticContent;
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
    private $flagMock;

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
        $this->loggerMock = $this->getMockBuilder(LoggerInterface::class)
            ->getMockForAbstractClass();
        $this->fileMock = $this->createMock(File::class);
        $this->directoryListMock = $this->createMock(DirectoryList::class);
        $this->remoteDiskIdentifierMock = $this->createMock(RemoteDiskIdentifier::class);
        $this->processMock = $this->getMockBuilder(ProcessInterface::class)
            ->getMockForAbstractClass();
        $this->flagFilePoolMock = $this->createMock(FlagFilePool::class);
        $this->flagMock = $this->getMockBuilder(FlagFileInterface::class)
            ->getMockForAbstractClass();
        $this->stageConfigMock = $this->getMockForAbstractClass(DeployInterface::class);

        $this->process = new DeployStaticContent(
            $this->processMock,
            $this->environmentMock,
            $this->loggerMock,
            $this->fileMock,
            $this->directoryListMock,
            $this->remoteDiskIdentifierMock,
            $this->flagFilePoolMock,
            $this->stageConfigMock
        );
    }

    public function testExecuteOnRemote()
    {
        $this->remoteDiskIdentifierMock->expects($this->once())
            ->method('isOnLocalDisk')
            ->with('pub/static')
            ->willReturn(false);
        $this->flagFilePoolMock->expects($this->never())
            ->method('getFlag');
        $this->environmentMock->expects($this->never())
            ->method('getApplicationMode');
        $this->environmentMock->expects($this->never())
            ->method('isDeployStaticContent');
        $this->directoryListMock->expects($this->never())
            ->method('getMagentoRoot');
        $this->fileMock->expects($this->never())
            ->method('backgroundClearDirectory');

        $this->process->execute();
    }

    public function testExecuteOnLocalWithoutCleaning()
    {
        $this->remoteDiskIdentifierMock->expects($this->once())
            ->method('isOnLocalDisk')
            ->with('pub/static')
            ->willReturn(true);
        $this->flagFilePoolMock->expects($this->once())
            ->method('getFlag')
            ->with('scd_pending')
            ->willReturn($this->flagMock);
        $this->flagMock->expects($this->once())
            ->method('exists')
            ->willReturn(true);
        $this->environmentMock->expects($this->once())
            ->method('getApplicationMode')
            ->willReturn(Environment::MAGENTO_PRODUCTION_MODE);
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
        $this->directoryListMock->expects($this->never())
            ->method('getMagentoRoot');
        $this->fileMock->expects($this->never())
            ->method('backgroundClearDirectory');

        $this->processMock->expects($this->once())
            ->method('execute');

        $this->process->execute();
    }

    public function testExecuteOnLocalNonProductionMode()
    {
        $this->remoteDiskIdentifierMock->expects($this->once())
            ->method('isOnLocalDisk')
            ->with('pub/static')
            ->willReturn(true);
        $this->flagFilePoolMock->expects($this->once())
            ->method('getFlag')
            ->with('scd_pending')
            ->willReturn($this->flagMock);
        $this->flagMock->expects($this->once())
            ->method('exists')
            ->willReturn(true);
        $this->environmentMock->expects($this->once())
            ->method('getApplicationMode')
            ->willReturn('Developer');
        $this->environmentMock->expects($this->never())
            ->method('isDeployStaticContent');
        $this->loggerMock->expects($this->never())
            ->method('info');

        $this->process->execute();
    }

    public function testExecuteOnLocalDoNotDeploy()
    {
        $this->remoteDiskIdentifierMock->expects($this->once())
            ->method('isOnLocalDisk')
            ->with('pub/static')
            ->willReturn(true);
        $this->flagFilePoolMock->expects($this->once())
            ->method('getFlag')
            ->with('scd_pending')
            ->willReturn($this->flagMock);
        $this->flagMock->expects($this->once())
            ->method('exists')
            ->willReturn(true);
        $this->environmentMock->expects($this->once())
            ->method('getApplicationMode')
            ->willReturn(Environment::MAGENTO_PRODUCTION_MODE);
        $this->environmentMock->expects($this->once())
            ->method('isDeployStaticContent')
            ->willReturn(false);
        $this->directoryListMock->expects($this->never())
            ->method('getMagentoRoot');
        $this->fileMock->expects($this->never())
            ->method('backgroundClearDirectory');
        $this->loggerMock->expects($this->never())
            ->method('info');

        $this->process->execute();
    }

    public function testDoCleanStaticFiles()
    {
        $magentoRoot = 'magento_root';
        $this->remoteDiskIdentifierMock->expects($this->once())
            ->method('isOnLocalDisk')
            ->with('pub/static')
            ->willReturn(true);
        $this->flagFilePoolMock->expects($this->once())
            ->method('getFlag')
            ->with('scd_pending')
            ->willReturn($this->flagMock);
        $this->flagMock->expects($this->once())
            ->method('exists')
            ->willReturn(true);
        $this->environmentMock->expects($this->once())
            ->method('getApplicationMode')
            ->willReturn(Environment::MAGENTO_PRODUCTION_MODE);
        $this->environmentMock->expects($this->once())
            ->method('isDeployStaticContent')
            ->willReturn(true);
        $this->directoryListMock->expects($this->once())
            ->method('getMagentoRoot')
            ->willReturn($magentoRoot);
        $this->fileMock->expects($this->exactly(2))
            ->method('backgroundClearDirectory')
            ->withConsecutive(
                [$magentoRoot . '/pub/static'],
                [$magentoRoot . '/var/view_preprocessed']
            );
        $this->loggerMock->expects($this->exactly(3))
            ->method('info')
            ->withConsecutive(
                ['Clearing pub/static'],
                ['Clearing var/view_preprocessed'],
                ['Generating fresh static content']
            );
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
}
