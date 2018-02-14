<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Unit\Filesystem\DirectoryCopier;

use Magento\MagentoCloud\Filesystem\DirectoryCopier\SubSymlinkStrategy;
use Magento\MagentoCloud\Filesystem\Driver\File;
use PHPUnit\Framework\TestCase;
use PHPUnit_Framework_MockObject_MockObject as Mock;
use Psr\Log\LoggerInterface;

/**
 * @inheritdoc
 */
class SubSymlinkStrategyTest extends TestCase
{
    /**
     * @var SubSymlinkStrategy
     */
    private $subSymlinkStrategy;

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

        $this->subSymlinkStrategy = new SubSymlinkStrategy($this->fileMock, $this->loggerMock);
    }

    public function testCopy()
    {
        $this->fileMock->expects($this->once())
            ->method('isExists')
            ->with('realFromDir')
            ->willReturn(true);

        $splFileInfoOne = $this->createFileInfoMock(false, 'file1');
        $splFileInfoTwo = $this->createFileInfoMock(false, 'file2');
        $splFileInfoDot = $this->createFileInfoMock(true, '.');

        $directoryIteratorMock = $this->createMock(\DirectoryIterator::class);
        $this->mockIterator($directoryIteratorMock, [
            $splFileInfoOne,
            $splFileInfoTwo,
            $splFileInfoDot,
        ]);
        $this->fileMock->expects($this->once())
            ->method('getDirectoryIterator')
            ->with('realFromDir')
            ->willReturn($directoryIteratorMock);
        $this->fileMock->expects($this->exactly(2))
            ->method('symlink')
            ->withConsecutive(
                ['realFromDir/file1', 'toDir/file1'],
                ['realFromDir/file2', 'toDir/file2']
            );

        $this->assertTrue($this->subSymlinkStrategy->copy('fromDir', 'toDir'));
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

        $this->subSymlinkStrategy->copy('fromDir', 'toDir');
    }

    /**
     * @param bool $isDot
     * @param string $fileName
     * @return Mock
     */
    private function createFileInfoMock(bool $isDot, string $fileName)
    {
        $splFileInfoMock = $this->createMock(\DirectoryIterator::class);
        $splFileInfoMock->expects($this->once())
            ->method('isDot')
            ->willReturn($isDot);
        if ($isDot) {
            $splFileInfoMock->expects($this->never())
                ->method('getFilename');
        } else {
            $splFileInfoMock->expects($this->exactly(2))
                ->method('getFilename')
                ->willReturn($fileName);
        }

        return $splFileInfoMock;
    }

    /**
     * Setup methods required to mock an iterator
     *
     * @param Mock $iteratorMock The mock to attach the iterator methods to
     * @param array $items The mock data we're going to use with the iterator
     * @return Mock The iterator mock
     */
    private function mockIterator(Mock $iteratorMock, array $items)
    {
        $iteratorData = new \stdClass();
        $iteratorData->array = $items;
        $iteratorData->position = 0;

        $iteratorMock->expects($this->any())
            ->method('rewind')
            ->will(
                $this->returnCallback(
                    function () use ($iteratorData) {
                        $iteratorData->position = 0;
                    }
                )
            );

        $iteratorMock->expects($this->any())
            ->method('current')
            ->will(
                $this->returnCallback(
                    function () use ($iteratorData) {
                        return $iteratorData->array[$iteratorData->position];
                    }
                )
            );

        $iteratorMock->expects($this->any())
            ->method('key')
            ->will(
                $this->returnCallback(
                    function () use ($iteratorData) {
                        return $iteratorData->position;
                    }
                )
            );

        $iteratorMock->expects($this->any())
            ->method('next')
            ->will(
                $this->returnCallback(
                    function () use ($iteratorData) {
                        $iteratorData->position++;
                    }
                )
            );

        $iteratorMock->expects($this->any())
            ->method('valid')
            ->will(
                $this->returnCallback(
                    function () use ($iteratorData) {
                        return isset($iteratorData->array[$iteratorData->position]);
                    }
                )
            );

        return $iteratorMock;
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

        $this->assertFalse($this->subSymlinkStrategy->copy('fromDir', 'toDir'));
    }
}
