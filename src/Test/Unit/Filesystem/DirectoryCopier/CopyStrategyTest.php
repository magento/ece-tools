<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Unit\Filesystem\DirectoryCopier;

use Magento\MagentoCloud\Filesystem\DirectoryCopier\CopyStrategy;
use Magento\MagentoCloud\Filesystem\Driver\File;
use PHPUnit\Framework\TestCase;
use PHPUnit_Framework_MockObject_MockObject as Mock;
use Psr\Log\LoggerInterface;

/**
 * @inheritdoc
 */
class CopyStrategyTest extends TestCase
{
    /**
     * @var CopyStrategy
     */
    private $copyStrategy;

    /**
     * @var File|Mock
     */
    private $fileMock;

    /**
     * @var LoggerInterface|Mock
     */
    private $loggerMock;

    protected function setUp()
    {
        $this->fileMock = $this->createMock(File::class);
        $this->loggerMock = $this->getMockBuilder(LoggerInterface::class)
            ->getMockForAbstractClass();

        $this->copyStrategy = new CopyStrategy($this->fileMock, $this->loggerMock);
    }

    public function testCopy()
    {
        $this->fileMock->expects($this->exactly(2))
            ->method('isExists')
            ->withConsecutive(
                ['fromDir'],
                ['toDir']
            )
            ->willReturn(true);
        $this->fileMock->expects($this->once())
            ->method('isLink')
            ->with('toDir')
            ->willReturn(false);
        $this->fileMock->expects($this->never())
            ->method('unLink');
        $this->fileMock->expects($this->once())
            ->method('copyDirectory')
            ->with('fromDir', 'toDir');

        $this->assertTrue($this->copyStrategy->copy('fromDir', 'toDir'));
    }

    public function testCopyToDirectoryIsLink()
    {
        $this->fileMock->expects($this->exactly(2))
            ->method('isExists')
            ->withConsecutive(
                ['fromDir'],
                ['toDir']
            )
            ->willReturnOnConsecutiveCalls(
                true,
                false
            );
        $this->fileMock->expects($this->once())
            ->method('isLink')
            ->with('toDir')
            ->willReturn(true);
        $this->fileMock->expects($this->once())
            ->method('unLink')
            ->with('toDir');
        $this->fileMock->expects($this->once())
            ->method('createDirectory')
            ->with('toDir');
        $this->fileMock->expects($this->once())
            ->method('copyDirectory')
            ->with('fromDir', 'toDir');

        $this->assertTrue($this->copyStrategy->copy('fromDir', 'toDir'));
    }

    /**
     * @expectedException \Magento\MagentoCloud\Filesystem\FileSystemException
     * @expectedExceptionMessage Can't copy directory fromDir. Directory does not exist.
     */
    public function testCopyFromDirNotExists()
    {
        $this->fileMock->expects($this->once())
            ->method('isExists')
            ->with('fromDir')
            ->willReturn(false);

        $this->copyStrategy->copy('fromDir', 'toDir');
    }

    public function testIsEmptyDirectory()
    {
        $this->fileMock->expects($this->once())
            ->method('isExists')
            ->with('fromDir')
            ->willReturn(true);

        $this->fileMock->expects($this->once())
            ->method('isEmptyDirectory')
            ->with('fromDir')
            ->willReturn(true);

        $this->loggerMock->expects($this->once())
            ->method('info')
            ->with('fromDir is empty. Nothing to restore');

        $this->assertFalse($this->copyStrategy->copy('fromDir', 'toDir'));
    }
}
