<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Unit\Util;

use Magento\MagentoCloud\Filesystem\DirectoryCopier\CopyStrategy;
use Magento\MagentoCloud\Filesystem\DirectoryCopier\StrategyFactory;
use Magento\MagentoCloud\Filesystem\DirectoryList;
use Magento\MagentoCloud\Filesystem\Driver\File;
use Magento\MagentoCloud\Filesystem\FileSystemException;
use Magento\MagentoCloud\Util\BuildDirCopier;
use PHPUnit\Framework\TestCase;
use PHPUnit_Framework_MockObject_MockObject as Mock;
use Psr\Log\LoggerInterface;

/**
 * @inheritdoc
 */
class BuildDirCopierTest extends TestCase
{
    /**
     * @var LoggerInterface|Mock
     */
    private $loggerMock;

    /**
     * @var DirectoryList|Mock
     */
    private $directoryListMock;

    /**
     * @var StrategyFactory|Mock
     */
    private $strategyFactory;

    /**
     * @var BuildDirCopier
     */
    private $copier;

    protected function setUp()
    {
        $this->loggerMock = $this->getMockBuilder(LoggerInterface::class)
            ->getMockForAbstractClass();
        $this->directoryListMock = $this->createMock(DirectoryList::class);
        $this->strategyFactory = $this->createMock(StrategyFactory::class);

        $this->copier = new BuildDirCopier(
            $this->loggerMock,
            $this->directoryListMock,
            $this->strategyFactory
        );
    }

    /**
     * @param boolean $result
     * @param string $logLevel
     * @param string $logMessage
     *
     * @dataProvider copyDataProvider
     */
    public function testCopy($result, $logLevel, $logMessage)
    {
        $strategy = 'copy';
        $rootDir = '/path/to/root';
        $initDir = $rootDir . '/init';
        $dir = 'dir';
        $fromDirectory = $initDir . '/' . $dir;
        $toDirectory = $rootDir . '/' . $dir;

        $copyStrategy = $this->createMock(CopyStrategy::class);
        $copyStrategy->expects($this->once())
            ->method('copy')
            ->with($fromDirectory, $toDirectory)
            ->willReturn($result);
        $this->strategyFactory->expects($this->once())
            ->method('create')
            ->with($strategy)
            ->willReturn($copyStrategy);
        $this->directoryListMock->expects($this->once())
            ->method('getMagentoRoot')
            ->willReturn($rootDir);
        $this->directoryListMock->expects($this->once())
            ->method('getInit')
            ->willReturn($initDir);
        $this->loggerMock->expects($this->once())
            ->method($logLevel)
            ->with($logMessage);

        $this->copier->copy($dir, $strategy);
    }

    /**
     * @return array
     */
    public function copyDataProvider()
    {
        return [
            [
                true,
                'debug',
                'Directory dir was copied with strategy: copy',
            ],
            [
                false,
                'notice',
                'Can\'t copy directory dir with strategy: copy',
            ],
        ];
    }

    public function testCopyMissingDestDirectory()
    {
        $strategy = 'copy';
        $rootDir = '/path/to/root';
        $initDir = $rootDir . '/init';
        $dir = 'not-exist-dir';
        $rootInitDir = $initDir . '/' . $dir;
        $fileMock = $this->createMock(File::class);
        $copyStrategy = new CopyStrategy($fileMock, $this->loggerMock);

        $this->directoryListMock->expects($this->once())
            ->method('getMagentoRoot')
            ->willReturn($rootDir);
        $this->directoryListMock->expects($this->once())
            ->method('getInit')
            ->willReturn($initDir);
        $this->strategyFactory->expects($this->once())
            ->method('create')
            ->with($strategy)
            ->willReturn($copyStrategy);
        $fileMock->expects($this->exactly(2))
            ->method('isExists')
            ->withConsecutive(
                [$rootInitDir],
                [$rootDir . '/' . $dir]
            )
            ->willReturnOnConsecutiveCalls(true, false);
        $fileMock->expects($this->once())
            ->method('createDirectory')
            ->with($rootDir . '/' . $dir);
        $fileMock->expects($this->once())
            ->method('isEmptyDirectory')
            ->with($rootInitDir)
            ->willReturn(false);
        $fileMock->expects($this->once())
            ->method('copyDirectory')
            ->with($rootInitDir, $rootDir . '/' . $dir)
            ->willReturn(false);
        $this->loggerMock->expects($this->once())
            ->method('debug')
            ->with('Directory ' . $dir . ' was copied with strategy: ' . $strategy);
        $this->loggerMock->expects($this->never())
            ->method('notice');

        $this->copier->copy($dir, $strategy);
    }

    public function testCopyWithFilesSystemException()
    {
        $strategy = 'copy';
        $rootDir = '/path/to/root';
        $initDir = $rootDir . '/init';
        $dir = 'dir';
        $fromDirectory = $initDir . '/' . $dir;
        $toDirectory = $rootDir . '/' . $dir;

        $copyStrategy = $this->createMock(CopyStrategy::class);
        $copyStrategy->expects($this->once())
            ->method('copy')
            ->with($fromDirectory, $toDirectory)
            ->willThrowException(
                new FileSystemException('Can\'t copy directory /path/to/root/not-exist-dir. Directory does not exist.')
            );
        $this->strategyFactory->expects($this->once())
            ->method('create')
            ->with($strategy)
            ->willReturn($copyStrategy);
        $this->directoryListMock->expects($this->once())
            ->method('getMagentoRoot')
            ->willReturn($rootDir);
        $this->directoryListMock->expects($this->once())
            ->method('getInit')
            ->willReturn($initDir);
        $this->loggerMock->expects($this->once())
            ->method('notice')
            ->with('Can\'t copy directory /path/to/root/not-exist-dir. Directory does not exist.');

        $this->copier->copy($dir, $strategy);
    }
}
