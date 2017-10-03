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
        $dir = 'dir';

        $this->directoryListMock->expects($this->once())
            ->method('getMagentoRoot')
            ->willReturn($rootDir);
        $this->fileMock->expects($this->once())
            ->method('isExists')
            ->with($rootDir . '/' . $dir)
            ->willReturn(true);
        $this->fileMock->expects($this->never())
            ->method('createDirectory');
        $this->fileMock->expects($this->once())
            ->method('copyDirectory')
            ->with($rootDir . '/init/' .$dir, $rootDir . '/' .$dir)
            ->willReturn(true);
        $this->loggerMock->expects($this->once())
            ->method('info')
            ->with('Copied directory: dir');

        $this->copier->copy($dir);
    }

    public function testCopyDirectoryNotExist()
    {
        $rootDir = '/path/to/root';
        $dir = 'not-exist-dir';

        $this->directoryListMock->expects($this->once())
            ->method('getMagentoRoot')
            ->willReturn($rootDir);
        $this->fileMock->expects($this->once())
            ->method('isExists')
            ->with($rootDir . '/' . $dir)
            ->willReturn(false);
        $this->fileMock->expects($this->once())
            ->method('createDirectory')
            ->with($rootDir . '/' . $dir);
        $this->fileMock->expects($this->once())
            ->method('copyDirectory')
            ->with($rootDir . '/init/' .$dir, $rootDir . '/' .$dir)
            ->willReturn(true);
        $this->loggerMock->expects($this->exactly(2))
            ->method('info')
            ->withConsecutive(
                ['Created directory: ' . $dir],
                ['Copied directory: ' . $dir]
            );

        $this->copier->copy($dir);
    }
}
