<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Unit\Filesystem\DirectoryCopier;

use Magento\MagentoCloud\Filesystem\DirectoryCopier\SymlinkStrategy;
use Magento\MagentoCloud\Filesystem\Driver\File;
use PHPUnit\Framework\TestCase;
use PHPUnit_Framework_MockObject_MockObject as Mock;

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

    protected function setUp()
    {
        $this->fileMock = $this->createMock(File::class);
        $this->fileMock->expects($this->exactly(2))
            ->method('getRealPath')
            ->withConsecutive(
                ['fromDir'],
                ['toDir']
            )
            ->willReturnOnConsecutiveCalls(
                'realFromDir',
                'realToDir'
            );

        $this->symlinkStrategy = new SymlinkStrategy($this->fileMock);
    }

    public function testCopy()
    {
        $this->fileMock->expects($this->exactly(2))
            ->method('isExists')
            ->withConsecutive(
                ['realFromDir'],
                ['realToDir']
            )
            ->willReturnOnConsecutiveCalls(
                true,
                false
            );
        $this->fileMock->expects($this->once())
            ->method('symlink')
            ->with('realFromDir', 'realToDir')
            ->willReturn(true);

        $this->assertTrue($this->symlinkStrategy->copy('fromDir', 'toDir'));
    }

    public function testCopyToExistsDirectory()
    {
        $this->fileMock->expects($this->exactly(2))
            ->method('isExists')
            ->withConsecutive(
                ['realFromDir'],
                ['realToDir']
            )
            ->willReturnOnConsecutiveCalls(
                true,
                true
            );
        $this->fileMock->expects($this->once())
            ->method('isLink')
            ->with('realToDir')
            ->willReturn(true);
        $this->fileMock->expects($this->once())
            ->method('unLink')
            ->with('realToDir');
        $this->fileMock->expects($this->never())
            ->method('deleteDirectory');
        $this->fileMock->expects($this->once())
            ->method('symlink')
            ->with('realFromDir', 'realToDir')
            ->willReturn(true);

        $this->assertTrue($this->symlinkStrategy->copy('fromDir', 'toDir'));
    }

    /**
     * @expectedException \Magento\MagentoCloud\Filesystem\FileSystemException
     * @expectedExceptionMessage Can't copy directory realFromDir. Directory does not exist.
     */
    public function testCopyFromDirNotExists()
    {
        $this->fileMock->expects($this->once())
            ->method('isExists')
            ->with('realFromDir')
            ->willReturn(false);

        $this->symlinkStrategy->copy('fromDir', 'toDir');
    }
}
