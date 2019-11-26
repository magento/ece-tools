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
use PHPUnit\Framework\TestCase;
use PHPUnit_Framework_MockObject_MockObject as Mock;
use Psr\Log\LoggerInterface;

/**
 * @inheritdoc
 */
class SymlinkStrategyTest extends TestCase
{
    /**
     * @var SymlinkStrategy
     */
    private $symlinkStrategy;

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
        $this->fileMock->expects($this->once())
            ->method('getRealPath')
            ->with('fromDir')
            ->willReturnOnConsecutiveCalls('realFromDir');
        $this->loggerMock = $this->getMockBuilder(LoggerInterface::class)
            ->getMockForAbstractClass();

        $this->symlinkStrategy = new SymlinkStrategy($this->fileMock, $this->loggerMock);
    }

    public function testCopy()
    {
        $this->fileMock->expects($this->exactly(2))
            ->method('isExists')
            ->withConsecutive(
                ['realFromDir'],
                ['toDir']
            )
            ->willReturnOnConsecutiveCalls(
                true,
                false
            );
        $this->fileMock->expects($this->once())
            ->method('symlink')
            ->with('realFromDir', 'toDir')
            ->willReturn(true);

        $this->assertTrue($this->symlinkStrategy->copy('fromDir', 'toDir'));
    }

    public function testCopyToExistsLinkedDirectory()
    {
        $this->fileMock->expects($this->exactly(2))
            ->method('isExists')
            ->withConsecutive(
                ['realFromDir'],
                ['toDir']
            )
            ->willReturnOnConsecutiveCalls(
                true,
                true
            );
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

    public function testCopyToExistsDirectory()
    {
        $this->fileMock->expects($this->exactly(2))
            ->method('isExists')
            ->withConsecutive(
                ['realFromDir'],
                ['toDir']
            )
            ->willReturnOnConsecutiveCalls(
                true,
                true
            );
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

    public function testIsEmptyDirectory()
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

    public function testCopyFromDirNotExists()
    {
        $this->expectException(FileSystemException::class);
        $this->expectExceptionMessage('Can\'t copy directory realFromDir. Directory does not exist.');

        $this->fileMock->expects($this->once())
            ->method('isExists')
            ->with('realFromDir')
            ->willReturn(false);

        $this->symlinkStrategy->copy('fromDir', 'toDir');
    }
}
