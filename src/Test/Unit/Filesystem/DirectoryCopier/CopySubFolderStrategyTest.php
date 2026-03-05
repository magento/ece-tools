<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Test\Unit\Filesystem\DirectoryCopier;

use Magento\MagentoCloud\Filesystem\DirectoryCopier\CopySubFolderStrategy;
use Magento\MagentoCloud\Filesystem\Driver\File;
use Magento\MagentoCloud\Filesystem\FileSystemException;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\MockObject\Rule\InvocationOrder;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * @inheritdoc
 */
#[AllowMockObjectsWithoutExpectations]
class CopySubFolderStrategyTest extends TestCase
{
    /**
     * @var CopySubFolderStrategy
     */
    private $copySubFolderStrategy;

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
        $this->loggerMock = $this->createMock(LoggerInterface::class);

        $this->copySubFolderStrategy = new CopySubFolderStrategy($this->fileMock, $this->loggerMock);
    }

    /**
     * Test copy method.
     *
     * @return void
     */
    public function testCopy(): void
    {
        $this->fileMock->expects($this->exactly(2))
            ->method('isExists')
            // withConsecutive() alternative.
            ->willReturnCallback(fn($param) => match ([$param]) {
                ['fromDir'] => true,
                ['toDir'] => false
            });
        $this->fileMock->expects($this->once())
            ->method('isLink')
            ->with('toDir')
            ->willReturn(false);
        $this->fileMock->expects($this->never())
            ->method('unLink');
        $this->fileMock->expects($this->once())
            ->method('createDirectory')
            ->with('toDir');
        $this->fileMock->expects($this->once())
            ->method('copyDirectory')
            ->with('fromDir', 'toDir');

        $this->assertTrue(
            $this->copySubFolderStrategy->copy(
                'fromDir',
                'toDir'
            )
        );
    }

    /**
     * Test copy to directory is link.
     *
     * @return void
     */
    public function testCopyToDirectoryIsLink(): void
    {
        $this->fileMock->expects($this->exactly(2))
            ->method('isExists')
            ->willReturnCallback(fn($param) => match ([$param]) {
                ['fromDir'] => true,
                ['toDir'] => false
            });
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

        $this->assertTrue($this->copySubFolderStrategy->copy('fromDir', 'toDir'));
    }

    /**
     * Test copy from directory not exists.
     *
     * @return void
     */
    public function testCopyFromDirNotExists(): void
    {
        $this->expectException(FileSystemException::class);
        $this->expectExceptionMessage('Cannot copy directory fromDir. Directory does not exist.');

        $this->fileMock->expects($this->once())
            ->method('isExists')
            ->with('fromDir')
            ->willReturn(false);

        $this->copySubFolderStrategy->copy('fromDir', 'toDir');
    }

    /**
     * Test is empty directory.
     *
     * @return void
     */
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

        $this->assertFalse($this->copySubFolderStrategy->copy('fromDir', 'toDir'));
    }

    /**
     * Test iterative copy.
     *
     * @return void
     */
    public function testIterativeCopy(): void
    {
        $splFileInfoOne = $this->createFileInfoMock('file1', false);
        $splFileInfoTwo = $this->createFileInfoMock('file2', false);
        $splFileInfoDot = $this->createFileInfoMock('.', true);

        $directoryIteratorMock = $this->createMock(\DirectoryIterator::class);
        $this->mockIterator($directoryIteratorMock, [
            $splFileInfoOne,
            $splFileInfoTwo,
            $splFileInfoDot,
        ]);

        $this->fileMock->expects($this->exactly(2))
            ->method('isExists')
            ->willReturnCallback(fn($param) => match ([$param]) {
                ['fromDir'] => true,
                ['toDir'] => true
            });
        $this->fileMock->expects($this->once())
            ->method('isLink')
            ->with('toDir')
            ->willReturn(false);
        $this->fileMock->expects($this->never())
            ->method('unLink');
        $this->fileMock->expects($this->once())
            ->method('getDirectoryIterator')
            ->with('fromDir')
            ->willReturn($directoryIteratorMock);
        $series = [
            ['fromDir/file1', 'toDir/file1'],
            ['fromDir/file2', 'toDir/file2']
        ];
        $matcher = $this->exactly(2);
        $this->fileMock->expects($matcher)
            ->method('copy')
            ->with(
                $this->callback(function ($param) use ($series, $matcher) {
                    $arguments = $series[$this->resolveInvocations($matcher) - 1];  // retrieves arguments
                    $this->assertStringContainsString($arguments[0], $param); // performs assertion on the argument
                    return true;
                }),
                $this->callback(function ($param) use ($series, $matcher) {
                    $arguments = $series[$this->resolveInvocations($matcher) - 1];  // retrieves arguments
                    $this->assertStringContainsString($arguments[1], $param); // performs assertion on the argument
                    return true;
                }),
            );
        $this->assertTrue($this->copySubFolderStrategy->copy('fromDir', 'toDir'));
    }

    /**
     * Create file info mock.
     *
     * @param string $fileName
     * @param bool $isDot
     * @return MockObject
     */
    private function createFileInfoMock(string $fileName, bool $isDot): MockObject
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

        $iteratorMock->expects($this->any())
            ->method('rewind')
            ->willReturnCallback(
                function () use ($iteratorData) {
                    $iteratorData->position = 0;
                }
            );

        $iteratorMock->expects($this->any())
            ->method('current')
            ->willReturnCallback(
                function () use ($iteratorData) {
                    return $iteratorData->array[$iteratorData->position];
                }
            );

        $iteratorMock->expects($this->any())
            ->method('key')
            ->willReturnCallback(
                function () use ($iteratorData) {
                    return $iteratorData->position;
                }
            );

        $iteratorMock->expects($this->any())
            ->method('next')
            ->willReturnCallback(
                function () use ($iteratorData) {
                    $iteratorData->position++;
                }
            );

        $iteratorMock->expects($this->any())
            ->method('valid')
            ->willReturnCallback(
                function () use ($iteratorData) {
                    return isset($iteratorData->array[$iteratorData->position]);
                }
            );

        return $iteratorMock;
    }

    /**
     * Resolve invocations.
     *
     * @param InvocationOrder $matcher
     * @return int
     */
    private function resolveInvocations(InvocationOrder $matcher): int
    {
        if (method_exists($matcher, 'numberOfInvocations')) {
            // PHPUnit 10+ (including PHPUnit 12)
            return $matcher->numberOfInvocations();
        }

        if (method_exists($matcher, 'getInvocationCount')) {
            // before PHPUnit 10
            return $matcher->getInvocationCount();
        }

        $this->fail('Cannot count the number of invocations.');
    }
}
