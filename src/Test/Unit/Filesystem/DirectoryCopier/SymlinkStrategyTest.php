<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Test\Unit\Filesystem\DirectoryCopier;

use Magento\MagentoCloud\Filesystem\DirectoryCopier\SymlinkStrategy;
use Magento\MagentoCloud\Filesystem\Driver\File;
use Magento\MagentoCloud\Filesystem\FileSystemException;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * @inheritdoc
 */
#[AllowMockObjectsWithoutExpectations]
class SymlinkStrategyTest extends TestCase
{
    /**
     * @var SymlinkStrategy
     */
    private $symlinkStrategy;

    /**
     * @var File|MockObject
     */
    private $fileMock;

    /**
     * @var LoggerInterface|MockObject
     */
    private $loggerMock;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        $this->fileMock = $this->createMock(File::class);
        $this->fileMock->expects($this->once())
            ->method('getRealPath')
            ->with('fromDir')
            ->willReturnOnConsecutiveCalls('realFromDir');
        $this->loggerMock = $this->createMock(LoggerInterface::class);

        $this->symlinkStrategy = new SymlinkStrategy($this->fileMock, $this->loggerMock);
    }

    /**
     * @throws FileSystemException
     */
    public function testCopy(): void
    {
        $this->fileMock->expects($this->exactly(2))
            ->method('isExists')
            ->willReturnCallback(function (...$args) {
                static $series = [
                    [['realFromDir'], true],
                    [['toDir'], false],
                ];

                [$expectedArgs, $return] = array_shift($series);
                $this->assertSame($expectedArgs, $args);

                return $return;
            });
        $this->fileMock->expects($this->once())
            ->method('symlink')
            ->with('realFromDir', 'toDir')
            ->willReturn(true);

        $this->assertTrue($this->symlinkStrategy->copy('fromDir', 'toDir'));
    }

    /**
     * @throws FileSystemException
     */
    public function testCopyToExistsLinkedDirectory(): void
    {
        $this->fileMock->expects($this->exactly(2))
            ->method('isExists')
            ->willReturnCallback(function (...$args) {
                static $series = [
                    [['realFromDir'], true],
                    [['toDir'], true],
                ];

                [$expectedArgs, $return] = array_shift($series);
                $this->assertSame($expectedArgs, $args);

                return $return;
            });
        $this->fileMock->expects($this->once())
            ->method('isLink')
            ->with('toDir')
            ->willReturn(true);
        $this->fileMock->expects($this->once())
            ->method('unLink')
            ->with('toDir');
        $this->fileMock->expects($this->never())
            ->method('deleteDirectory');
        $this->fileMock->expects($this->once())
            ->method('symlink')
            ->with('realFromDir', 'toDir')
            ->willReturn(true);

        $this->assertTrue($this->symlinkStrategy->copy('fromDir', 'toDir'));
    }

    /**
     * @throws FileSystemException
     */
    public function testCopyToExistsDirectory(): void
    {
        $this->fileMock->expects($this->exactly(2))
            ->method('isExists')
            ->willReturnCallback(function (...$args) {
                static $series = [
                    [['realFromDir'], true],
                    [['toDir'], true],
                ];

                [$expectedArgs, $return] = array_shift($series);
                $this->assertSame($expectedArgs, $args);

                return $return;
            });
        $this->fileMock->expects($this->once())
            ->method('isLink')
            ->with('toDir')
            ->willReturn(false);
        $this->fileMock->expects($this->never())
            ->method('unLink');
        $this->fileMock->expects($this->once())
            ->method('deleteDirectory')
            ->with('toDir');
        $this->fileMock->expects($this->once())
            ->method('symlink')
            ->with('realFromDir', 'toDir')
            ->willReturn(true);

        $this->assertTrue($this->symlinkStrategy->copy('fromDir', 'toDir'));
    }

    /**
     * @throws FileSystemException
     */
    public function testIsEmptyDirectory(): void
    {
        $this->fileMock->expects($this->once())
            ->method('getRealPath')
            ->with('fromDir')
            ->willReturn('realFromDir');

        $this->fileMock->expects($this->once())
            ->method('isExists')
            ->with('realFromDir')
            ->willReturn(true);

        $this->fileMock->expects($this->once())
            ->method('isEmptyDirectory')
            ->with('realFromDir')
            ->willReturn(true);

        $this->loggerMock->expects($this->once())
            ->method('info')
            ->with('realFromDir is empty. Nothing to restore');

        $this->assertFalse($this->symlinkStrategy->copy('fromDir', 'toDir'));
    }

    /**
     * @throws FileSystemException
     */
    public function testCopyFromDirNotExists(): void
    {
        $this->expectException(FileSystemException::class);
        $this->expectExceptionMessage('Cannot copy directory realFromDir. Directory does not exist.');

        $this->fileMock->expects($this->once())
            ->method('isExists')
            ->with('realFromDir')
            ->willReturn(false);

        $this->symlinkStrategy->copy('fromDir', 'toDir');
    }
}
