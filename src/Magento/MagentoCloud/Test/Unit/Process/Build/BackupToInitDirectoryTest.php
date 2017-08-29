<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Unit\Process\Build;

use Magento\MagentoCloud\Config\Environment;
use Magento\MagentoCloud\Filesystem\Driver\File;
use Magento\MagentoCloud\Process\Build\BackupToInitDirectory;
use Magento\MagentoCloud\Shell\ShellInterface;
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
     * @var ShellInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $shellMock;

    /**
     * @var Environment|\PHPUnit_Framework_MockObject_MockObject
     */
    private $environmentMock;

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
        $this->shellMock = $this->getMockBuilder(ShellInterface::class)
            ->getMockForAbstractClass();
        $this->environmentMock = $this->getMockBuilder(Environment::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->process = new BackupToInitDirectory(
            $this->fileMock,
            $this->loggerMock,
            $this->shellMock,
            $this->environmentMock
        );
    }

    public function testProcess()
    {
        $this->fileMock->method('isExists')
            ->willReturnMap([
                [Environment::REGENERATE_FLAG, true],
                ['./init/pub/static', true],
            ]);
        $this->loggerMock->method('notice')
            ->withConsecutive(
                ['Removing .regenerate flag'],
                ['Moving static content to init directory'],
                ['Remove ./init/pub/static'],
                ['Copying writable directories to temp directory.']
            );
        $this->environmentMock->expects($this->once())
            ->method('isStaticDeployInBuild')
            ->willReturn(true);
        $this->shellMock->expects($this->any())
            ->method('execute')
            ->withConsecutive(
                ['mkdir -p ./init/pub/'],
                ['cp -R ./pub/static/ ./init/pub/static'],
                ['mkdir -p init/some_dir'],
                ['mkdir -p some_dir'],
                ['/bin/bash -c "shopt -s dotglob; cp -R some_dir/* ./init/some_dir/"'],
                ['rm -rf some_dir'],
                ['mkdir -p some_dir']
            );
        $this->fileMock->method('deleteFile')
            ->withConsecutive(
                [Environment::REGENERATE_FLAG],
                ['./init/pub/static']
            );
        $this->fileMock->expects($this->once())
            ->method('copy')
            ->with(
                MAGENTO_ROOT . Environment::STATIC_CONTENT_DEPLOY_FLAG,
                MAGENTO_ROOT . 'init/' . Environment::STATIC_CONTENT_DEPLOY_FLAG
            );
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
        $this->fileMock->method('isExists')
            ->willReturnMap([
                [Environment::REGENERATE_FLAG, false],
                ['./init/pub/static', true],
            ]);
        $this->loggerMock->method('notice')
            ->withConsecutive(
                ['Moving static content to init directory'],
                ['Remove ./init/pub/static'],
                ['Copying writable directories to temp directory.']
            );
        $this->environmentMock->expects($this->once())
            ->method('isStaticDeployInBuild')
            ->willReturn(true);
        $this->shellMock->expects($this->any())
            ->method('execute')
            ->withConsecutive(
                ['mkdir -p ./init/pub/'],
                ['cp -R ./pub/static/ ./init/pub/static'],
                ['mkdir -p init/some_dir'],
                ['mkdir -p some_dir'],
                ['/bin/bash -c "shopt -s dotglob; cp -R some_dir/* ./init/some_dir/"'],
                ['rm -rf some_dir'],
                ['mkdir -p some_dir']
            );
        $this->fileMock->method('deleteFile')
            ->withConsecutive(
                ['./init/pub/static']
            );
        $this->fileMock->expects($this->once())
            ->method('copy')
            ->with(
                MAGENTO_ROOT . Environment::STATIC_CONTENT_DEPLOY_FLAG,
                MAGENTO_ROOT . 'init/' . Environment::STATIC_CONTENT_DEPLOY_FLAG
            );
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
        $this->fileMock->method('isExists')
            ->willReturnMap([
                [Environment::REGENERATE_FLAG, false],
                ['./init/pub/static', true],
            ]);
        $this->loggerMock->method('notice')
            ->withConsecutive(
                ['No file ' . Environment::STATIC_CONTENT_DEPLOY_FLAG],
                ['Copying writable directories to temp directory.']
            );
        $this->environmentMock->expects($this->once())
            ->method('isStaticDeployInBuild')
            ->willReturn(false);
        $this->shellMock->expects($this->any())
            ->method('execute')
            ->withConsecutive(
                ['mkdir -p init/some_dir'],
                ['mkdir -p some_dir'],
                ['/bin/bash -c "shopt -s dotglob; cp -R some_dir/* ./init/some_dir/"'],
                ['rm -rf some_dir'],
                ['mkdir -p some_dir']
            );
        $this->fileMock->expects($this->never())
            ->method('deleteFile');
        $this->fileMock->expects($this->never())
            ->method('copy');
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
        $this->fileMock->method('isExists')
            ->willReturnMap([
                [Environment::REGENERATE_FLAG, false],
                ['./init/pub/static', true],
            ]);
        $this->loggerMock->method('notice')
            ->withConsecutive(
                ['No file ' . Environment::STATIC_CONTENT_DEPLOY_FLAG],
                ['Copying writable directories to temp directory.']
            );
        $this->environmentMock->expects($this->once())
            ->method('isStaticDeployInBuild')
            ->willReturn(false);
        $this->shellMock->expects($this->never())
            ->method('execute');
        $this->fileMock->expects($this->never())
            ->method('deleteFile');
        $this->fileMock->expects($this->never())
            ->method('copy');
        $this->environmentMock->expects($this->once())
            ->method('getWritableDirectories')
            ->willReturn([]);
        $this->fileMock->expects($this->never())
            ->method('scanDir');

        $this->process->execute();
    }
}
