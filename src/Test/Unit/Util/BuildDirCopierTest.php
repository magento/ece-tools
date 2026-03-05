<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Test\Unit\Util;

use Magento\MagentoCloud\App\Error;
use Magento\MagentoCloud\Filesystem\DirectoryCopier\CopyStrategy;
use Magento\MagentoCloud\Filesystem\DirectoryCopier\StrategyFactory;
use Magento\MagentoCloud\Filesystem\DirectoryList;
use Magento\MagentoCloud\Filesystem\Driver\File;
use Magento\MagentoCloud\Filesystem\FileSystemException;
use Magento\MagentoCloud\Util\BuildDirCopier;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * @inheritdoc
 */
class BuildDirCopierTest extends TestCase
{
    /**
     * @var BuildDirCopier
     */
    private $copier;

    /**
     * @var LoggerInterface|MockObject
     */
    private $loggerMock;

    /**
     * @var DirectoryList|MockObject
     */
    private $directoryListMock;

    /**
     * @var StrategyFactory|MockObject
     */
    private $strategyFactory;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        $this->loggerMock        = $this->createMock(LoggerInterface::class);
        $this->directoryListMock = $this->createMock(DirectoryList::class);
        $this->strategyFactory   = $this->createMock(StrategyFactory::class);

        $this->copier = new BuildDirCopier(
            $this->loggerMock,
            $this->directoryListMock,
            $this->strategyFactory
        );
    }

    /**
     * Test copy method.
     *
     * @param boolean $result
     * @param string $logLevel
     * @param string $logMessage
     * @dataProvider copyDataProvider
     * @return void
     */
    #[DataProvider('copyDataProvider')]
    public function testCopy(bool $result, string $logLevel, string $logMessage): void
    {
        $strategy      = 'copy';
        $rootDir       = '/path/to/root';
        $initDir       = $rootDir . '/init';
        $dir           = 'dir';
        $fromDirectory = $initDir . '/' . $dir;
        $toDirectory   = $rootDir . '/' . $dir;

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
     * Data provider for copy method.
     *
     * @return array
     */
    public static function copyDataProvider(): array
    {
        return [
            [
                true,
                'debug',
                'Directory dir was copied with strategy: copy',
            ],
            [
                false,
                'warning',
                'Cannot copy directory dir with strategy: copy',
            ],
        ];
    }

    /**
     * Test copy missing destination directory method.
     *
     * @return void
     */
    public function testCopyMissingDestDirectory(): void
    {
        $strategy    = 'copy';
        $rootDir     = '/path/to/root';
        $initDir     = $rootDir . '/init';
        $dir         = 'not-exist-dir';
        $rootInitDir = $initDir . '/' . $dir;
        /** @var File|MockObject $fileMock */
        $fileMock     = $this->createMock(File::class);
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
            // withConsecutive() alternative.
            ->willReturnCallback(fn($param) => match ([$param]) {
                [$rootInitDir] => true,
                [$rootDir . '/' . $dir] => false
            });
        $fileMock->expects($this->once())
            ->method('createDirectory')
            ->with($rootDir . '/' . $dir);
        $fileMock->expects($this->once())
            ->method('isEmptyDirectory')
            ->with($rootInitDir)
            ->willReturn(false);
        $fileMock->expects($this->once())
            ->method('copyDirectory')
            ->with($rootInitDir, $rootDir . '/' . $dir);
        $this->loggerMock->expects($this->once())
            ->method('debug')
            ->with('Directory ' . $dir . ' was copied with strategy: ' . $strategy);
        $this->loggerMock->expects($this->never())
            ->method('notice');

        $this->copier->copy($dir, $strategy);
    }

    /**
     * Test copy with filesystem exception.
     *
     * @return void
     */
    public function testCopyWithFilesSystemException(): void
    {
        $strategy      = 'copy';
        $rootDir       = '/path/to/root';
        $initDir       = $rootDir . '/init';
        $dir           = 'dir';
        $fromDirectory = $initDir . '/' . $dir;
        $toDirectory   = $rootDir . '/' . $dir;

        $copyStrategy = $this->createMock(CopyStrategy::class);
        $copyStrategy->expects($this->once())
            ->method('copy')
            ->with($fromDirectory, $toDirectory)
            ->willThrowException(
                new FileSystemException('Cannot copy directory /path/to/root/not-exist-dir. Directory does not exist.')
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
            ->method('warning')
            ->with(
                'Cannot copy directory /path/to/root/not-exist-dir. Directory does not exist.',
                ['errorCode' => Error::WARN_COPY_MOUNTED_DIRS_FAILED]
            );

        $this->copier->copy($dir, $strategy);
    }
}
