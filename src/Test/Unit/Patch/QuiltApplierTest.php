<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Unit\Patch;

use Magento\MagentoCloud\Filesystem\DirectoryList;
use Magento\MagentoCloud\Filesystem\Driver\File;
use Magento\MagentoCloud\Patch\QuiltApplier;
use Magento\MagentoCloud\Shell\ShellInterface;
use PHPUnit\Framework\TestCase;
use PHPUnit_Framework_MockObject_MockObject as Mock;
use Psr\Log\LoggerInterface;

/**
 * Class ApplierTest.
 */
class QuiltApplierTest extends TestCase
{
    /**
     * @var QuiltApplier
     */
    private $applier;

    /**
     * @var ShellInterface|Mock
     */
    private $shellMock;

    /**
     * @var LoggerInterface|Mock
     */
    private $loggerMock;

    /**
     * @var DirectoryList|Mock
     */
    private $directoryListMock;

    /**
     * @var File|Mock
     */
    private $fileMock;

    protected function setUp()
    {
        $this->shellMock = $this->getMockForAbstractClass(ShellInterface::class);
        $this->loggerMock = $this->getMockForAbstractClass(LoggerInterface::class);
        $this->directoryListMock = $this->createMock(DirectoryList::class);
        $this->fileMock = $this->createMock(File::class);

        $this->applier = new QuiltApplier(
            $this->shellMock,
            $this->loggerMock,
            $this->directoryListMock,
            $this->fileMock
        );
    }

    public function testApplyPatchesWhenSeriesDoesntYetExist()
    {
        $path = 'path/to/patch';
        $name = 'patchName';
        $seriesPath = 'root/series';
        $quiltMocOutput = 'Quilt moc command output.';

        $this->directoryListMock->expects($this->any())
            ->method('getPatches')
            ->willReturn('root');
        $this->fileMock->expects($this->once())
            ->method('isExists')
            ->with($seriesPath)
            ->willReturn(false);
        $this->shellMock->expects($this->once())
            ->method('execute')
            ->with('QUILT_PATCHES=\'root\' quilt push -a ; EXIT_CODE=$? ;'
                . ' if { [ 0 -eq "$EXIT_CODE" ] || [ 2 -eq "$EXIT_CODE" ]; }; then true; else false ; fi')
            ->willReturn([$quiltMocOutput]);
        $this->fileMock->expects($this->once())
            ->method('filePutContents')
            ->with($seriesPath, $path . "\n");
        $this->loggerMock->expects($this->never())
            ->method('notice');
        $this->loggerMock->expects($this->exactly(3))
            ->method('info')
            ->withConsecutive(
                ['* Running quilt started.'],
                [$quiltMocOutput],
                ['* Running quilt finished.']
            );

        $this->applier->applyPatches([['path' => $path, 'name' => $name]]);
    }

    public function testApplyPatchesWhenSeriesAlreadyExists()
    {
        $path = 'path/to/patch';
        $name = 'patchName';
        $seriesPath = 'root/series';
        $quiltMocOutput = 'Quilt moc command output.';

        $this->directoryListMock->expects($this->any())
            ->method('getPatches')
            ->willReturn('root');
        $this->fileMock->expects($this->once())
            ->method('isExists')
            ->with($seriesPath)
            ->willReturn(true);
        $this->shellMock->expects($this->exactly(2))
            ->method('execute')
            ->withConsecutive(
                ['QUILT_PATCHES=\'root\' quilt pop -a ; EXIT_CODE=$? ;'
                    . ' if { [ 0 -eq "$EXIT_CODE" ] || [ 2 -eq "$EXIT_CODE" ]; }; then true; else false ; fi'],
                ['QUILT_PATCHES=\'root\' quilt push -a ; EXIT_CODE=$? ;'
                    . ' if { [ 0 -eq "$EXIT_CODE" ] || [ 2 -eq "$EXIT_CODE" ]; }; then true; else false ; fi']
            )
            ->willReturn([$quiltMocOutput]);
        $this->fileMock->expects($this->once())
            ->method('filePutContents')
            ->with($seriesPath, $path . "\n");
        $this->loggerMock->expects($this->never())
            ->method('notice');
        $this->loggerMock->expects($this->exactly(5))
            ->method('info')
            ->withConsecutive(
                ['Unapplying patches started.'],
                ['Unapplying patches finished.'],
                ['* Running quilt started.'],
                [$quiltMocOutput],
                ['* Running quilt finished.']
            );

        $this->applier->applyPatches([['path' => $path, 'name' => $name]]);
    }

    /**
     * @expectedException RuntimeException
     * @expectedExceptionMessage Applying the patch has failed for some reason
     */
    public function testApplyPatchesFailsWhenQuiltPopFails()
    {
        $path = 'path/to/patch';
        $name = 'patchName';
        $seriesPath = 'root/series';
        $quiltMocOutput = 'Quilt moc command output.';

        $this->directoryListMock->expects($this->any())
            ->method('getPatches')
            ->willReturn('root');
        $this->fileMock->expects($this->once())
            ->method('isExists')
            ->with($seriesPath)
            ->willReturn(true);
        $this->shellMock->expects($this->exactly(1))
            ->method('execute')
            ->withConsecutive(
                ['QUILT_PATCHES=\'root\' quilt pop -a ; EXIT_CODE=$? ;'
                    .' if { [ 0 -eq "$EXIT_CODE" ] || [ 2 -eq "$EXIT_CODE" ]; }; then true; else false ; fi'],
                ['QUILT_PATCHES=\'root\' quilt push -a ; EXIT_CODE=$? ;'
                    . ' if { [ 0 -eq "$EXIT_CODE" ] || [ 2 -eq "$EXIT_CODE" ]; }; then true; else false ; fi']
            )
            ->willReturnCallback(function () {
                throw new \RuntimeException('Applying the patch has failed for some reason');
            });
        $this->fileMock->expects($this->never())
            ->method('filePutContents');
            //->with($seriesPath, $path . "\n");
        $this->loggerMock->expects($this->never())
            ->method('notice');
        $this->loggerMock->expects($this->exactly(1))
            ->method('info')
            ->withConsecutive(
                ['Unapplying patches started.'],
                ['Unapplying patches finished.'],
                ['* Running quilt started.'],
                [$quiltMocOutput],
                ['* Running quilt finished.']
            );

        $this->applier->applyPatches([['path' => $path, 'name' => $name]]);
    }

    /**
     * @expectedException RuntimeException
     * @expectedExceptionMessage Applying the patch has failed for some reason
     */
    public function testApplyPatchesFailsWhenQuiltPushFails()
    {
        $path = 'path/to/patch';
        $name = 'patchName';
        $seriesPath = 'root/series';
        $quiltMocOutput = 'Quilt moc command output.';

        $this->directoryListMock->expects($this->any())
            ->method('getPatches')
            ->willReturn('root');
        $this->fileMock->expects($this->once())
            ->method('isExists')
            ->with($seriesPath)
            ->willReturn(false);
        $this->shellMock->expects($this->exactly(1))
            ->method('execute')
            ->withConsecutive(
                ['QUILT_PATCHES=\'root\' quilt push -a ; EXIT_CODE=$? ;'
                    . ' if { [ 0 -eq "$EXIT_CODE" ] || [ 2 -eq "$EXIT_CODE" ]; }; then true; else false ; fi']
            )
            ->willReturnCallback(function () {
                throw new \RuntimeException('Applying the patch has failed for some reason');
            });
        $this->fileMock->expects($this->once())
            ->method('filePutContents')
            ->with($seriesPath, $path . "\n");
        $this->loggerMock->expects($this->never())
            ->method('notice');
        $this->loggerMock->expects($this->exactly(1))
            ->method('info')
            ->withConsecutive(
                ['* Running quilt started.'],
                [$quiltMocOutput],
                ['* Running quilt finished.']
            );

        $this->applier->applyPatches([['path' => $path, 'name' => $name]]);
    }
}
