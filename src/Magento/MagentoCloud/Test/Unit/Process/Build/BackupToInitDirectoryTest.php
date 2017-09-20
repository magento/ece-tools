<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Unit\Process\Build;

use Magento\MagentoCloud\Config\Environment;
use Magento\MagentoCloud\Filesystem\DirectoryList;
use Magento\MagentoCloud\Filesystem\Driver\File;
use Magento\MagentoCloud\Process\Build\BackupToInitDirectory;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * @inheritdoc
 */
class BackupToInitDirectoryTest extends TestCase
{
    /**
     * @var BackupToInitDirectory
     */
    private $process;

    /**
     * @var File|\PHPUnit_Framework_MockObject_MockObject
     */
    private $fileMock;

    /**
     * @var LoggerInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $loggerMock;

    /**
     * @var Environment|\PHPUnit_Framework_MockObject_MockObject
     */
    private $environmentMock;

    /**
     * @var DirectoryList|\PHPUnit_Framework_MockObject_MockObject
     */
    private $directoryListMock;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        $this->fileMock = $this->getMockBuilder(File::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->loggerMock = $this->getMockBuilder(LoggerInterface::class)
            ->getMockForAbstractClass();
        $this->environmentMock = $this->getMockBuilder(Environment::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->directoryListMock = $this->getMockBuilder(DirectoryList::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->directoryListMock->expects($this->once())
            ->method('getMagentoRoot')
            ->willReturn('magento_root');

        $this->process = new BackupToInitDirectory(
            $this->fileMock,
            $this->loggerMock,
            $this->environmentMock,
            $this->directoryListMock
        );
    }

    public function testProcess()
    {
        $this->fileMock->expects($this->exactly(2))
            ->method('isExists')
            ->willReturnMap([
                ['magento_root/' . Environment::REGENERATE_FLAG, true],
                ['magento_root/init/pub/static/', true],
            ]);
        $this->fileMock->expects($this->once())
            ->method('deleteFile')
            ->with('magento_root/' . Environment::REGENERATE_FLAG)
            ->willReturn(true);
        $this->fileMock->expects($this->exactly(4))
            ->method('createDirectory')
            ->withConsecutive(
                ['magento_root/init/pub/'],
                ['magento_root/init/some_dir'],
                ['magento_root/some_dir'],
                ['magento_root/some_dir']
            )
            ->willReturn(true);
        $this->fileMock->expects($this->exactly(2))
            ->method('deleteDirectory')
            ->withConsecutive(
                ['magento_root/init/pub/static/'],
                ['magento_root/some_dir']
            )
            ->willReturn(true);
        $this->fileMock->expects($this->exactly(2))
            ->method('copyDirectory')
            ->withConsecutive(
                ['magento_root/pub/static/', 'magento_root/init/pub/static/'],
                ['magento_root/some_dir', 'magento_root/init/some_dir']
            )
            ->willReturn(true);
        $this->fileMock->expects($this->once())
            ->method('copy')
            ->with(
                'magento_root/' . Environment::STATIC_CONTENT_DEPLOY_FLAG,
                'magento_root/init/' . Environment::STATIC_CONTENT_DEPLOY_FLAG
            );
        $this->loggerMock->expects($this->exactly(4))
            ->method('info')
            ->withConsecutive(
                ['Removing .regenerate flag'],
                ['Moving static content to init directory'],
                ['Remove ./init/pub/static'],
                ['Copying writable directories to temp directory.']
            );
        $this->environmentMock->expects($this->once())
            ->method('isStaticDeployInBuild')
            ->willReturn(true);
        $this->environmentMock->expects($this->once())
            ->method('getWritableDirectories')
            ->willReturn(['some_dir']);
        $this->fileMock->expects($this->once())
            ->method('scanDir')
            ->willReturn(['dir1', 'dir2', 'dir3']);

        $this->process->execute();
    }

    public function testProcessNoRegenerateFile()
    {
        $this->fileMock->expects($this->exactly(2))
            ->method('isExists')
            ->willReturnMap([
                ['magento_root/' . Environment::REGENERATE_FLAG, false],
                ['magento_root/init/pub/static/', true],
            ]);
        $this->fileMock->expects($this->never())
            ->method('deleteFile')
            ->with('magento_root/' . Environment::REGENERATE_FLAG);
        $this->fileMock->expects($this->exactly(4))
            ->method('createDirectory')
            ->withConsecutive(
                ['magento_root/init/pub/'],
                ['magento_root/init/some_dir'],
                ['magento_root/some_dir'],
                ['magento_root/some_dir']
            )
            ->willReturn(true);
        $this->fileMock->expects($this->exactly(2))
            ->method('deleteDirectory')
            ->withConsecutive(
                ['magento_root/init/pub/static/'],
                ['magento_root/some_dir']
            )
            ->willReturn(true);
        $this->fileMock->expects($this->exactly(2))
            ->method('copyDirectory')
            ->withConsecutive(
                ['magento_root/pub/static/', 'magento_root/init/pub/static/'],
                ['magento_root/some_dir', 'magento_root/init/some_dir']
            )
            ->willReturn(true);
        $this->fileMock->expects($this->once())
            ->method('copy')
            ->with(
                'magento_root/' . Environment::STATIC_CONTENT_DEPLOY_FLAG,
                'magento_root/init/' . Environment::STATIC_CONTENT_DEPLOY_FLAG
            );
        $this->loggerMock->expects($this->exactly(3))
            ->method('info')
            ->withConsecutive(
                ['Moving static content to init directory'],
                ['Remove ./init/pub/static'],
                ['Copying writable directories to temp directory.']
            );
        $this->environmentMock->expects($this->once())
            ->method('isStaticDeployInBuild')
            ->willReturn(true);
        $this->environmentMock->expects($this->once())
            ->method('getWritableDirectories')
            ->willReturn(['some_dir']);
        $this->fileMock->expects($this->once())
            ->method('scanDir')
            ->willReturn(['dir1', 'dir2', 'dir3']);

        $this->process->execute();
    }

    public function testProcessNoStaticDeployInBuild()
    {
        $this->fileMock->expects($this->once())
            ->method('isExists')
            ->willReturnMap([
                ['magento_root/' . Environment::REGENERATE_FLAG, false]
            ]);
        $this->fileMock->expects($this->never())
            ->method('deleteFile')
            ->with('magento_root/' . Environment::REGENERATE_FLAG);
        $this->fileMock->expects($this->exactly(3))
            ->method('createDirectory')
            ->withConsecutive(
                ['magento_root/init/some_dir'],
                ['magento_root/some_dir'],
                ['magento_root/some_dir']
            )
            ->willReturn(true);
        $this->fileMock->expects($this->once())
            ->method('deleteDirectory')
            ->with('magento_root/some_dir')
            ->willReturn(true);
        $this->fileMock->expects($this->once())
            ->method('copyDirectory')
            ->with('magento_root/some_dir', 'magento_root/init/some_dir')
            ->willReturn(true);
        $this->fileMock->expects($this->never())
            ->method('copy');
        $this->loggerMock->expects($this->exactly(2))
            ->method('info')
            ->withConsecutive(
                ['No file ' . Environment::STATIC_CONTENT_DEPLOY_FLAG],
                ['Copying writable directories to temp directory.']
            );
        $this->environmentMock->expects($this->once())
            ->method('isStaticDeployInBuild')
            ->willReturn(false);
        $this->environmentMock->expects($this->once())
            ->method('getWritableDirectories')
            ->willReturn(['some_dir']);
        $this->fileMock->expects($this->once())
            ->method('scanDir')
            ->willReturn(['dir1', 'dir2', 'dir3']);

        $this->process->execute();
    }

    public function testProcessNoWritableDirs()
    {
        $this->fileMock->expects($this->once())
            ->method('isExists')
            ->willReturnMap([
                ['magento_root/' . Environment::REGENERATE_FLAG, false]
            ]);
        $this->fileMock->expects($this->never())
            ->method('deleteFile')
            ->with('magento_root/' . Environment::REGENERATE_FLAG);
        $this->fileMock->expects($this->never())
            ->method('createDirectory');
        $this->fileMock->expects($this->never())
            ->method('deleteDirectory');
        $this->fileMock->expects($this->never())
            ->method('copyDirectory');
        $this->fileMock->expects($this->never())
            ->method('copy');
        $this->loggerMock->expects($this->exactly(2))
            ->method('info')
            ->withConsecutive(
                ['No file ' . Environment::STATIC_CONTENT_DEPLOY_FLAG],
                ['Copying writable directories to temp directory.']
            );
        $this->environmentMock->expects($this->once())
            ->method('isStaticDeployInBuild')
            ->willReturn(false);
        $this->environmentMock->expects($this->once())
            ->method('getWritableDirectories')
            ->willReturn([]);
        $this->fileMock->expects($this->never())
            ->method('scanDir');

        $this->process->execute();
    }
}
