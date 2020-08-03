<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Test\Unit\Filesystem\DirectoryCopier;

use Magento\MagentoCloud\Filesystem\DirectoryCopier\CopyStrategy;
use Magento\MagentoCloud\Filesystem\Driver\File;
use Magento\MagentoCloud\Filesystem\FileSystemException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
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
     * @var File|MockObject
     */
    private $fileMock;

    /**
     * @var LoggerInterface|MockObject
     */
    private $loggerMock;

    protected function setUp(): void
    {
        $this->fileMock = $this->createMock(File::class);
        $this->loggerMock = $this->getMockBuilder(LoggerInterface::class)
            ->getMockForAbstractClass();

        $this->copyStrategy = new CopyStrategy($this->fileMock, $this->loggerMock);
    }

    /**
     * @throws FileSystemException
     */
    public function testCopy(): void
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

    public function testCopyToDirectoryIsLink(): void
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

    public function testCopyFromDirNotExists(): void
    {
        $this->expectException(FileSystemException::class);
        $this->expectExceptionMessage('Cannot copy directory fromDir. Directory does not exist.');

        $this->fileMock->expects($this->once())
            ->method('isExists')
            ->with('fromDir')
            ->willReturn(false);

        $this->copyStrategy->copy('fromDir', 'toDir');
    }

    public function testIsEmptyDirectory(): void
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
