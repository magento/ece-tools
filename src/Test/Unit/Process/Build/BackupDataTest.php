<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Unit\Process\Build;

use Magento\MagentoCloud\App\Logger\Pool as LoggerPool;
use Magento\MagentoCloud\Config\Environment;
use Magento\MagentoCloud\Filesystem\DirectoryList;
use Magento\MagentoCloud\Filesystem\Driver\File;
use Magento\MagentoCloud\Filesystem\Flag\Manager as FlagManager;
use Magento\MagentoCloud\Process\Build\BackupData;
use PHPUnit\Framework\TestCase;
use PHPUnit_Framework_MockObject_MockObject as Mock;
use Psr\Log\LoggerInterface;

/**
 * @inheritdoc
 */
class BackupDataTest extends TestCase
{
    /**
     * @var BackupData
     */
    private $process;

    /**
     * @var File|Mock
     */
    private $fileMock;

    /**
     * @var LoggerInterface|Mock
     */
    private $loggerMock;

    /**
     * @var Environment|Mock
     */
    private $environmentMock;

    /**
     * @var DirectoryList|Mock
     */
    private $directoryListMock;

    /**
     * @var FlagManager|Mock
     */
    private $flagManagerMock;

    /**
     * @var string
     */
    private $rootInitDir;

    /**
     * @var string
     */
    private $pubStatic;

    /**
     * @var string
     */
    private $initPubStatic;

    /**
     * @var string
     */
    private $someDir;

    /**
     * @var string
     */
    private $initSomeDir;
    /**
     * @var LoggerPool|\PHPUnit_Framework_MockObject_MockObject
     */
    private $loggerPoolMock;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        $this->fileMock = $this->createMock(File::class);
        $this->loggerMock = $this->getMockBuilder(LoggerInterface::class)
            ->setMethods(['setHandlers', 'info'])
            ->getMockForAbstractClass();
        $this->environmentMock = $this->createMock(Environment::class);
        $this->directoryListMock = $this->createMock(DirectoryList::class);
        $this->flagManagerMock = $this->createMock(FlagManager::class);
        $this->rootInitDir = 'magento_root/init';
        $this->pubStatic = 'magento_root/pub/static/';
        $this->initPubStatic = 'magento_root/init/pub/static/';
        $this->someDir = 'magento_root/some_dir';
        $this->initSomeDir = 'magento_root/init/some_dir';
        $this->environmentMock = $this->getMockBuilder(Environment::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->directoryListMock = $this->getMockBuilder(DirectoryList::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->loggerPoolMock = $this->createMock(LoggerPool::class);

        $this->directoryListMock->expects($this->once())
            ->method('getMagentoRoot')
            ->willReturn('magento_root');
        $this->directoryListMock->expects($this->once())
            ->method('getInit')
            ->willReturn('magento_root/init');
        $this->flagManagerMock->expects($this->once())
            ->method('delete')
            ->with(FlagManager::FLAG_REGENERATE);

        $this->loggerPoolMock->expects($this->once())
            ->method('getHandlers')
            ->willReturn(['handler1', 'handler2']);
        $this->loggerMock->expects($this->exactly(2))
            ->method('setHandlers')
            ->withConsecutive(
                [[]],
                [['handler1', 'handler2']]
            );

        $this->process = new BackupData(
            $this->fileMock,
            $this->loggerMock,
            $this->environmentMock,
            $this->directoryListMock,
            $this->flagManagerMock,
            $this->loggerPoolMock
        );
    }

    public function testExecute()
    {
        $this->flagManagerMock->expects($this->once())
            ->method('exists')
            ->with(FlagManager::FLAG_STATIC_CONTENT_DEPLOY_IN_BUILD)
            ->willReturn(true);
        $this->flagManagerMock->expects($this->once())
            ->method('getFlagPath')
            ->with(FlagManager::FLAG_STATIC_CONTENT_DEPLOY_IN_BUILD)
            ->willReturn('flag/path');
        $this->loggerMock->expects($this->exactly(3))
            ->method('info')
            ->withConsecutive(
                ['Moving static content to init directory'],
                ['Remove ./init/pub/static'],
                ['Copying writable directories to temp directory.']
            );
        $this->fileMock->expects($this->exactly(5))
            ->method('createDirectory')
            ->withConsecutive(
                [$this->rootInitDir . '/pub/'],
                [$this->initPubStatic],
                [$this->initSomeDir],
                [$this->someDir],
                [$this->someDir]
            )
            ->willReturn(true);
        $this->fileMock->expects($this->once())
            ->method('isExists')
            ->with($this->initPubStatic)
            ->willReturn(true);
        $this->fileMock->expects($this->exactly(2))
            ->method('deleteDirectory')
            ->withConsecutive(
                [$this->initPubStatic],
                [$this->someDir]
            );
        $this->fileMock->expects($this->exactly(2))
            ->method('copyDirectory')
            ->withConsecutive(
                [$this->pubStatic, $this->initPubStatic],
                [$this->someDir, $this->initSomeDir]
            );
        $this->environmentMock->expects($this->once())
            ->method('getWritableDirectories')
            ->willReturn(['some_dir']);
        $this->fileMock->expects($this->once())
            ->method('scanDir')
            ->willReturn(['dir1', 'dir2', 'dir3']);

        $this->process->execute();
    }

    public function testExecuteSCDInDeploy()
    {
        $this->flagManagerMock->expects($this->once())
            ->method('exists')
            ->with(FlagManager::FLAG_STATIC_CONTENT_DEPLOY_IN_BUILD)
            ->willReturn(false);
        $this->flagManagerMock->expects($this->never())
            ->method('getFlagPath');
        $this->loggerMock->expects($this->exactly(2))
            ->method('info')
            ->withConsecutive(
                ['SCD not performed during build'],
                ['Copying writable directories to temp directory.']
            );
        $this->environmentMock->expects($this->once())
            ->method('getWritableDirectories')
            ->willReturn(['some_dir']);
        $this->fileMock->expects($this->exactly(3))
            ->method('createDirectory')
            ->withConsecutive(
                [$this->initSomeDir],
                [$this->someDir],
                [$this->someDir]
            )
            ->willReturn(true);
        $this->fileMock->expects($this->once())
            ->method('scanDir')
            ->willReturn(['dir1', 'dir2', 'dir3']);
        $this->fileMock->expects($this->once())
            ->method('copyDirectory')
            ->with($this->someDir, $this->initSomeDir);
        $this->fileMock->expects($this->once())
            ->method('deleteDirectory')
            ->with($this->someDir);

        $this->process->execute();
    }

    public function testExecuteNoWritableDirs()
    {
        $this->flagManagerMock->expects($this->once())
            ->method('exists')
            ->with(FlagManager::FLAG_STATIC_CONTENT_DEPLOY_IN_BUILD)
            ->willReturn(true);
        $this->flagManagerMock->expects($this->once())
            ->method('getFlagPath')
            ->with(FlagManager::FLAG_STATIC_CONTENT_DEPLOY_IN_BUILD)
            ->willReturn('flag/path');
        $this->loggerMock->expects($this->exactly(3))
            ->method('info')
            ->withConsecutive(
                ['Moving static content to init directory'],
                ['Remove ./init/pub/static'],
                ['Copying writable directories to temp directory.']
            );
        $this->fileMock->expects($this->exactly(2))
            ->method('createDirectory')
            ->withConsecutive(
                [$this->rootInitDir . '/pub/'],
                [$this->initPubStatic]
            )
            ->willReturn(true);
        $this->fileMock->expects($this->once())
            ->method('isExists')
            ->with($this->initPubStatic)
            ->willReturn(true);
        $this->fileMock->expects($this->once())
            ->method('deleteDirectory')
            ->with($this->initPubStatic);
        $this->fileMock->expects($this->once())
            ->method('copyDirectory')
            ->with($this->pubStatic, $this->initPubStatic);
        $this->environmentMock->expects($this->once())
            ->method('getWritableDirectories')
            ->willReturn([]);
        $this->fileMock->expects($this->never())
            ->method('scanDir');

        $this->process->execute();
    }
}
