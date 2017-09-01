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
        $this->fileMock = $this->getMockBuilder(File::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->directoryListMock = $this->getMockBuilder(DirectoryList::class)
            ->disableOriginalConstructor()
            ->getMock();

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
     * @param bool $preprocessedExists
     * @param int $logInfoExpects
     * @param int $backgroundExecuteExpects
     * @param int $renameExpects
     * @dataProvider cleanDataProvider
     * @return void
     */
    public function testClean(
        $oldStaticExists,
        $createOldStaticExpects,
        $preprocessedExists,
        $logInfoExpects,
        $backgroundExecuteExpects,
        $renameExpects
    ) {
        $magentoRoot = '/magento/';
        $pubStatic = $magentoRoot . 'pub/static';
        $var = $magentoRoot . 'var';
        $varPreprocessed = $var . '/view_preprocessed';
        $time = 123456;
        $oldStaticContentLocation = $pubStatic . '/old_static_content_' . $time . '/';
        $oldPreprocessedLocation = $varPreprocessed . '_old_' . $time;

        $timeMock = $this->getFunctionMock('Magento\MagentoCloud\Util', 'time');
        $timeMock->expects($this->once())
            ->willReturn($time);

        $this->directoryListMock->expects($this->once())
            ->method('getMagentoRoot')
            ->willReturn($magentoRoot);

        $this->loggerMock->expects($this->exactly($logInfoExpects))
            ->method('info')
            ->withConsecutive(
                ['Moving out old static content into ' . $oldStaticContentLocation],
                ['Rename ' . $pubStatic . '/folder1 to ' . $oldStaticContentLocation . 'folder1'],
                ['Rename ' . $pubStatic . '/file1.jpg to ' . $oldStaticContentLocation . 'file1.jpg'],
                ['Rename ' . $pubStatic . '/file2.jpg to ' . $oldStaticContentLocation . 'file2.jpg'],
                ['Removing ' . $oldStaticContentLocation . ' in the background'],
                ['Rename ' . $varPreprocessed . ' to ' . $oldPreprocessedLocation],
                ['Removing '.  $oldPreprocessedLocation . ' in the background']
            );

        $this->shellMock->expects($this->exactly($backgroundExecuteExpects))
            ->method('backgroundExecute')
            ->withConsecutive(
                ['rm -rf ' . $oldStaticContentLocation],
                ['rm -rf ' . $oldPreprocessedLocation]
            );

        $this->fileMock->expects($this->exactly(2))
            ->method('getRealPath')
            ->willReturnMap([
                [$pubStatic, $pubStatic],
                [$var, $var]
            ]);
        $this->fileMock->expects($this->exactly($renameExpects))
            ->method('rename')
            ->withConsecutive(
                [$pubStatic . '/folder1', $oldStaticContentLocation . 'folder1'],
                [$pubStatic . '/file1.jpg', $oldStaticContentLocation . 'file1.jpg'],
                [$pubStatic . '/file2.jpg', $oldStaticContentLocation . 'file2.jpg'],
                [$varPreprocessed, $oldPreprocessedLocation]
            );
        $this->fileMock->expects($this->exactly(2))
            ->method('isExists')
            ->willReturnMap([
                [$oldStaticContentLocation, $oldStaticExists],
                [$varPreprocessed, $preprocessedExists],
            ]);
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

        $this->staticContentCleaner->clean();
    }

    public function cleanDataProvider()
    {
        return [
            [
                'oldStaticExists' => true,
                'createOldStaticExpects' => 0,
                'preprocessedExists' => true,
                'logInfoExpects' => 7,
                'backgroundExecuteExpects' => 2,
                'renameExpects' => 4,
            ],
            [
                'oldStaticExists' => false,
                'createOldStaticExpects' => 1,
                'preprocessedExists' => true,
                'logInfoExpects' => 7,
                'backgroundExecuteExpects' => 2,
                'renameExpects' => 4,
            ],
            [
                'oldStaticExists' => false,
                'createOldStaticExpects' => 1,
                'preprocessedExists' => false,
                'logInfoExpects' => 5,
                'backgroundExecuteExpects' => 1,
                'renameExpects' => 3,
            ],
        ];
    }
}
