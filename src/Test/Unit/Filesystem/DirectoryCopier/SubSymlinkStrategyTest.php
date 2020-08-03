<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Test\Unit\Filesystem\DirectoryCopier;

use Magento\MagentoCloud\Filesystem\DirectoryCopier\SubSymlinkStrategy;
use Magento\MagentoCloud\Filesystem\Driver\File;
use Magento\MagentoCloud\Filesystem\FileSystemException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
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
        $this->loggerMock = $this->getMockBuilder(LoggerInterface::class)
            ->getMockForAbstractClass();

        $this->subSymlinkStrategy = new SubSymlinkStrategy($this->fileMock, $this->loggerMock);
    }

    /**
     * @throws FileSystemException
     */
    public function testCopy(): void
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
     * @throws FileSystemException
     */
    public function testCopyFromDirNotExists(): void
    {
        $this->expectException(FileSystemException::class);
        $this->expectExceptionMessage('Cannot copy directory "realFromDir". Directory does not exist.');
        $this->fileMock->expects($this->once())
            ->method('isExists')
            ->with('realFromDir')
            ->willReturn(false);

        $this->subSymlinkStrategy->copy('fromDir', 'toDir');
    }

    /**
     * @param bool $isDot
     * @param string $fileName
     * @return MockObject
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
     * @param MockObject $iteratorMock The mock to attach the iterator methods to
     * @param array $items The mock data we're going to use with the iterator
     * @return MockObject The iterator mock
     */
    private function mockIterator(MockObject $iteratorMock, array $items): MockObject
    {
        $iteratorData = new \stdClass();
        $iteratorData->array = $items;
        $iteratorData->position = 0;

        $iteratorMock->method('rewind')
            ->willReturnCallback(
                static function () use ($iteratorData) {
                    $iteratorData->position = 0;
                }
            );

        $iteratorMock->method('current')
            ->willReturnCallback(
                static function () use ($iteratorData) {
                    return $iteratorData->array[$iteratorData->position];
                }
            );

        $iteratorMock->method('key')
            ->willReturnCallback(
                static function () use ($iteratorData) {
                    return $iteratorData->position;
                }
            );

        $iteratorMock->expects($this->any())
            ->method('next')
            ->willReturnCallback(
                static function () use ($iteratorData) {
                    $iteratorData->position++;
                }
            );

        $iteratorMock->expects($this->any())
            ->method('valid')
            ->willReturnCallback(
                static function () use ($iteratorData) {
                    return isset($iteratorData->array[$iteratorData->position]);
                }
            );

        return $iteratorMock;
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
            ->with('Directory "realFromDir" is empty. Nothing to restore');

        $this->assertFalse($this->subSymlinkStrategy->copy('fromDir', 'toDir'));
    }
}
