<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Unit\Util;

use Magento\MagentoCloud\Util\StaticContentCleaner;
use Magento\MagentoCloud\Filesystem\Driver\File;
use Magento\MagentoCloud\Filesystem\DirectoryList;
use Magento\MagentoCloud\Shell\ShellInterface;
use Psr\Log\LoggerInterface;
use PHPUnit\Framework\TestCase;

/**
 * @inheritdoc
 */
class StaticContentCleanerTest extends TestCase
{
    use \phpmock\phpunit\PHPMock;

    /**
     * @var StaticContentCleaner
     */
    private $staticContentCleaner;

    /**
     * @var LoggerInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $loggerMock;

    /**
     * @var ShellInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $shellMock;

    /**
     * @var File|\PHPUnit_Framework_MockObject_MockObject
     */
    private $fileMock;

    /**
     * @var DirectoryList|\PHPUnit_Framework_MockObject_MockObject
     */
    private $directoryListMock;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        $this->loggerMock = $this->getMockBuilder(LoggerInterface::class)
            ->getMockForAbstractClass();
        $this->shellMock = $this->getMockBuilder(ShellInterface::class)
            ->getMockForAbstractClass();
        $this->fileMock = $this->createMock(File::class);
        $this->directoryListMock = $this->createMock(DirectoryList::class);

        $this->staticContentCleaner = new StaticContentCleaner(
            $this->loggerMock,
            $this->shellMock,
            $this->fileMock,
            $this->directoryListMock
        );
    }

    /**
     * @param bool $oldStaticExists
     * @param int $createOldStaticExpects
     * @dataProvider cleanDataProvider
     * @return void
     */
    public function testCleanPubStatic(
        $oldStaticExists,
        $createOldStaticExpects
    ) {
        $magentoRoot = '/magento/';
        $pubStatic = $magentoRoot . '/pub/static';
        $time = 123456;
        $oldStaticContentLocation = $pubStatic . '/old_static_content_' . $time . '/';

        $timeMock = $this->getFunctionMock('Magento\MagentoCloud\Util', 'time');
        $timeMock->expects($this->once())
            ->willReturn($time);

        $this->directoryListMock->expects($this->once())
            ->method('getMagentoRoot')
            ->willReturn($magentoRoot);

        $this->loggerMock->expects($this->exactly(5))
            ->method('info')
            ->withConsecutive(
                ['Moving out old static content into ' . $oldStaticContentLocation],
                ['Rename ' . $pubStatic . '/folder1 to ' . $oldStaticContentLocation . 'folder1'],
                ['Rename ' . $pubStatic . '/file1.jpg to ' . $oldStaticContentLocation . 'file1.jpg'],
                ['Rename ' . $pubStatic . '/file2.jpg to ' . $oldStaticContentLocation . 'file2.jpg'],
                ['Removing ' . $oldStaticContentLocation . ' in the background']
            );

        $this->shellMock->expects($this->once())
            ->method('backgroundExecute')
            ->withConsecutive(
                ['rm -rf ' . $oldStaticContentLocation]
            );

        $this->fileMock->expects($this->once())
            ->method('getRealPath')
            ->with($pubStatic)
            ->willReturn($pubStatic);
        $this->fileMock->expects($this->exactly(3))
            ->method('rename')
            ->withConsecutive(
                [$pubStatic . '/folder1', $oldStaticContentLocation . 'folder1'],
                [$pubStatic . '/file1.jpg', $oldStaticContentLocation . 'file1.jpg'],
                [$pubStatic . '/file2.jpg', $oldStaticContentLocation . 'file2.jpg']
            );
        $this->fileMock->expects($this->once())
            ->method('isExists')
            ->with($oldStaticContentLocation)
            ->willReturn($oldStaticExists);
        $this->fileMock->expects($this->exactly($createOldStaticExpects))
            ->method('createDirectory')
            ->with($oldStaticContentLocation);
        $this->fileMock->expects($this->once())
            ->method('readDirectory')
            ->with($pubStatic . '/')
            ->willReturn([
                $pubStatic . '/folder1',
                $pubStatic . '/file1.jpg',
                $oldStaticContentLocation . 'folder2',
                $pubStatic . '/file2.jpg',
            ]);

        $this->staticContentCleaner->cleanPubStatic();
    }

    public function cleanDataProvider()
    {
        return [
            [
                'oldStaticExists' => true,
                'createOldStaticExpects' => 0,
            ],
            [
                'oldStaticExists' => false,
                'createOldStaticExpects' => 1,
            ]
        ];
    }

    public function testCleanViewPreprocessedExists()
    {
        $time = 123456;
        $magentoRootDir = '/magento';
        $magentoVarDir = $magentoRootDir . '/var';
        $magentoVarPreprocessedDir = $magentoVarDir . '/view_preprocessed';
        $oldPreprocessedLocation = $magentoVarPreprocessedDir . '_old_' . $time;

        $timeMock = $this->getFunctionMock('Magento\MagentoCloud\Util', 'time');
        $timeMock->expects($this->once())
            ->willReturn($time);

        $this->directoryListMock->expects($this->once())
            ->method('getMagentoRoot')
            ->willReturn($magentoRootDir);
        $this->fileMock->expects($this->once())
            ->method('getRealPath')
            ->with($magentoVarDir)
            ->willReturn($magentoVarDir);
        $this->fileMock->expects($this->once())
            ->method('isExists')
            ->with($magentoVarDir . '/view_preprocessed')
            ->willReturn(true);
        $this->loggerMock->expects($this->exactly(2))
            ->method('info')
            ->withConsecutive(
                ['Rename ' . $magentoVarPreprocessedDir . ' to ' . $oldPreprocessedLocation],
                ['Removing '.  $oldPreprocessedLocation . ' in the background']
            );
        $this->fileMock->expects($this->once())
            ->method('rename')
            ->with($magentoVarPreprocessedDir, $oldPreprocessedLocation);
        $this->shellMock->expects($this->once())
            ->method('backgroundExecute')
            ->with('rm -rf ' . $oldPreprocessedLocation);

        $this->staticContentCleaner->cleanViewPreprocessed();
    }

    public function testCleanViewPreprocessedNotExists()
    {
        $magentoRootDir = '/magento/';
        $magentoVarDir = $magentoRootDir . '/var';

        $this->directoryListMock->expects($this->once())
            ->method('getMagentoRoot')
            ->willReturn($magentoRootDir);
        $this->fileMock->expects($this->once())
            ->method('getRealPath')
            ->with($magentoVarDir)
            ->willReturn($magentoVarDir);
        $this->fileMock->expects($this->once())
            ->method('isExists')
            ->with($magentoVarDir . '/view_preprocessed')
            ->willReturn(false);

        $this->fileMock->expects($this->never())
            ->method('rename');
        $this->shellMock->expects($this->never())
            ->method('backgroundExecute');
        $this->loggerMock->expects($this->never())
            ->method('info');

        $this->staticContentCleaner->cleanViewPreprocessed();
    }
}
