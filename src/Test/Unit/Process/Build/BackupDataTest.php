<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Unit\Process\Build;

use Magento\MagentoCloud\Config\Environment;
use Magento\MagentoCloud\Filesystem\DirectoryList;
use Magento\MagentoCloud\Filesystem\Driver\File;
use Magento\MagentoCloud\Filesystem\FlagFileInterface;
use Magento\MagentoCloud\Filesystem\FlagFilePool;
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
     * @var FlagFilePool|Mock
     */
    private $flagFilePoolMock;

    /**
     * @var FlagFileInterface|Mock
     */
    private $flagMock;

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
     * @inheritdoc
     */
    protected function setUp()
    {
        $this->fileMock = $this->createMock(File::class);
        $this->loggerMock = $this->getMockBuilder(LoggerInterface::class)
            ->getMockForAbstractClass();
        $this->environmentMock = $this->createMock(Environment::class);
        $this->directoryListMock = $this->createMock(DirectoryList::class);
        $this->flagFilePoolMock = $this->createMock(FlagFilePool::class);
        $this->flagMock = $this->getMockBuilder(FlagFileInterface::class)
            ->getMockForAbstractClass();
        $this->rootInitDir = 'magento_root/init';
        $this->pubStatic = 'magento_root/pub/static/';
        $this->initPubStatic = 'magento_root/init/pub/static/';
        $this->someDir = 'magento_root/some_dir';
        $this->initSomeDir = 'magento_root/init/some_dir';

        $this->directoryListMock->expects($this->once())
            ->method('getMagentoRoot')
            ->willReturn('magento_root');
        $this->directoryListMock->expects($this->once())
            ->method('getInit')
            ->willReturn('magento_root/init');
        $this->flagFilePoolMock->expects($this->exactly(2))
            ->method('getFlag')
            ->willReturnMap([
                ['regenerate', $this->flagMock],
                ['scd_in_build', $this->flagMock],
            ]);
        $this->flagMock->expects($this->once())
            ->method('delete');

        $this->process = new BackupData(
            $this->fileMock,
            $this->loggerMock,
            $this->environmentMock,
            $this->directoryListMock,
            $this->flagFilePoolMock
        );
    }

    public function testExecute()
    {
        $this->flagMock->expects($this->once())
            ->method('exists')
            ->willReturn(true);
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
        $this->flagMock->expects($this->once())
            ->method('exists')
            ->willReturn(false);
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
        $this->flagMock->expects($this->once())
            ->method('exists')
            ->willReturn(true);
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
