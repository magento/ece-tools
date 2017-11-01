<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Unit\Util;

use Magento\MagentoCloud\Filesystem\DirectoryList;
use Magento\MagentoCloud\Filesystem\Driver\File;
use Magento\MagentoCloud\Util\BuildDirCopier;
use PHPUnit\Framework\TestCase;
use PHPUnit_Framework_MockObject_MockObject as Mock;
use Psr\Log\LoggerInterface;

class BuildDirCopierTest extends TestCase
{
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
     * @var BuildDirCopier
     */
    private $copier;

    protected function setUp()
    {
        $this->loggerMock = $this->getMockBuilder(LoggerInterface::class)
            ->getMockForAbstractClass();
        $this->fileMock = $this->createMock(File::class);
        $this->directoryListMock = $this->createMock(DirectoryList::class);

        $this->copier = new BuildDirCopier(
            $this->loggerMock,
            $this->directoryListMock,
            $this->fileMock
        );
    }

    public function testCopy()
    {
        $rootDir = '/path/to/root';
        $initDir = $rootDir . '/init';
        $dir = 'dir';
        $rootInitDir = $initDir . '/' . $dir;

        $this->directoryListMock->expects($this->once())
            ->method('getMagentoRoot')
            ->willReturn($rootDir);
        $this->directoryListMock->expects($this->once())
            ->method('getInit')
            ->willReturn($initDir);
        $this->fileMock->expects($this->exactly(2))
            ->method('isExists')
            ->withConsecutive(
                [$rootInitDir],
                [$rootDir . '/' . $dir]
            )
            ->willReturnOnConsecutiveCalls(true, true);
        $this->fileMock->expects($this->never())
            ->method('createDirectory');
        $this->fileMock->expects($this->once())
            ->method('copyDirectory')
            ->with($rootInitDir, $rootDir . '/' .$dir)
            ->willReturn(true);
        $this->loggerMock->expects($this->once())
            ->method('info')
            ->with('Copied directory: dir');
        $this->loggerMock->expects($this->never())
            ->method('notice');

        $this->copier->copy($dir);
    }

    public function testCopyDirectoryNotExist()
    {
        $rootDir = '/path/to/root';
        $initDir = $rootDir . '/init';
        $dir = 'not-exist-dir';
        $rootInitDir = $initDir . '/' . $dir;

        $this->directoryListMock->expects($this->once())
            ->method('getMagentoRoot')
            ->willReturn($rootDir);
        $this->directoryListMock->expects($this->once())
            ->method('getInit')
            ->willReturn($initDir);
        $this->fileMock->expects($this->exactly(2))
            ->method('isExists')
            ->withConsecutive(
                [$rootInitDir],
                [$rootDir . '/' . $dir]
            )
            ->willReturnOnConsecutiveCalls(true, false);
        $this->fileMock->expects($this->once())
            ->method('createDirectory')
            ->with($rootDir . '/' . $dir);
        $this->fileMock->expects($this->once())
            ->method('copyDirectory')
            ->with($rootInitDir, $rootDir . '/' .$dir)
            ->willReturn(true);
        $this->loggerMock->expects($this->exactly(2))
            ->method('info')
            ->withConsecutive(
                ['Created directory: ' . $dir],
                ['Copied directory: ' . $dir]
            );
        $this->loggerMock->expects($this->never())
            ->method('notice');

        $this->copier->copy($dir);
    }

    public function testCopyInitDirectoryNotExists()
    {
        $rootDir = '/path/to/root';
        $initDir = $rootDir . '/init';
        $dir = 'not-exist-dir';
        $rootInitDir = $initDir . '/' . $dir;

        $this->directoryListMock->expects($this->once())
            ->method('getMagentoRoot')
            ->willReturn($rootDir);
        $this->directoryListMock->expects($this->once())
            ->method('getInit')
            ->willReturn($initDir);
        $this->fileMock->expects($this->once())
            ->method('isExists')
            ->with($rootInitDir)
            ->willReturn(false);
        $this->loggerMock->expects($this->once())
            ->method('notice')
            ->with('Can\'t copy directory /path/to/root. Directory does not exist.');

        $this->fileMock->expects($this->never())
            ->method('createDirectory');
        $this->fileMock->expects($this->never())
            ->method('copyDirectory');
        $this->loggerMock->expects($this->never())
            ->method('info');

        $this->copier->copy($dir);
    }
}
