<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Unit\Process\Build\BackupData;

use Magento\MagentoCloud\Config\Stage\BuildInterface;
use Magento\MagentoCloud\Filesystem\DirectoryList;
use Magento\MagentoCloud\Filesystem\Driver\File;
use Magento\MagentoCloud\Process\Build\BackupData\WritableDirectories;
use PHPUnit\Framework\TestCase;
use PHPUnit_Framework_MockObject_MockObject as Mock;
use Psr\Log\LoggerInterface;

/**
 * @inheritdoc
 */
class WritableDirectoriesTest extends TestCase
{
    /**
     * @var WritableDirectories
     */
    public $process;

    /**
     * @var File|Mock
     */
    private $fileMock;

    /**
     * @var LoggerInterface|Mock
     */
    private $loggerMock;

    /**
     * @var DirectoryList|Mock
     */
    private $directoryListMock;

    /**
     * @var BuildInterface|Mock
     */
    private $stageConfigMock;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        $this->fileMock = $this->createMock(File::class);
        $this->loggerMock = $this->getMockBuilder(LoggerInterface::class)
            ->setMethods(['info'])
            ->getMockForAbstractClass();
        $this->directoryListMock = $this->createMock(DirectoryList::class);
        $this->stageConfigMock = $this->getMockForAbstractClass(BuildInterface::class);

        $this->process = new WritableDirectories(
            $this->fileMock,
            $this->loggerMock,
            $this->directoryListMock,
            $this->stageConfigMock
        );
    }

    public function testExecuteCopyingViewPreprocessedDir()
    {
        $magentoRoot = 'magento_root';
        $rootInitDir = 'magento_root/init';

        $this->loggerMock->expects($this->once())
            ->method('info')
            ->with('Copying writable directories to temp directory.');

        $this->directoryListMock->expects($this->once())
            ->method('getWritableDirectories')
            ->willReturn([
                'some/path/to/the/directory1/exists',
                'var/view_preprocessed',
                'some/path/to/the/directory2/does/not/exist',
            ]);

        $this->stageConfigMock->expects($this->once())
            ->method('get')
            ->willReturn(false);

        $this->directoryListMock->expects($this->once())
            ->method('getMagentoRoot')
            ->willReturn($magentoRoot);

        $this->directoryListMock->expects($this->once())
            ->method('getInit')
            ->willReturn($rootInitDir);

        $this->fileMock->expects($this->exactly(3))
            ->method('isExists')
            ->withConsecutive(
                [$magentoRoot . '/some/path/to/the/directory1/exists'],
                [$magentoRoot . '/var/view_preprocessed'],
                [$magentoRoot . '/some/path/to/the/directory2/does/not/exist']
            )
            ->willReturnOnConsecutiveCalls(true, true, false);

        $this->fileMock->expects($this->exactly(2))
            ->method('createDirectory')
            ->withConsecutive(
                [$rootInitDir . '/some/path/to/the/directory1/exists'],
                [$rootInitDir . '/var/view_preprocessed']
            );
        $this->fileMock->expects($this->exactly(2))
            ->method('copyDirectory')
            ->withConsecutive(
                [
                    $magentoRoot . '/some/path/to/the/directory1/exists',
                    $rootInitDir . '/some/path/to/the/directory1/exists',
                ],
                [
                    $magentoRoot . '/var/view_preprocessed',
                    $rootInitDir . '/var/view_preprocessed',
                ]
            );

        $this->process->execute();
    }

    public function testExecuteSkipCopyingViewPreprocessedDir()
    {
        $magentoRoot = 'magento_root';
        $rootInitDir = 'magento_root/init';

        $this->loggerMock->expects($this->once())
            ->method('info')
            ->with('Copying writable directories to temp directory.');

        $this->directoryListMock->expects($this->once())
            ->method('getWritableDirectories')
            ->willReturn([
                'some/path/to/the/directory1/exists',
                'var/view_preprocessed',
                'some/path/to/the/directory2/does/not/exist',
            ]);

        $this->stageConfigMock->expects($this->once())
            ->method('get')
            ->willReturn(true);

        $this->directoryListMock->expects($this->once())
            ->method('getMagentoRoot')
            ->willReturn($magentoRoot);

        $this->directoryListMock->expects($this->once())
            ->method('getInit')
            ->willReturn($rootInitDir);

        $this->fileMock->expects($this->exactly(2))
            ->method('isExists')
            ->withConsecutive(
                [$magentoRoot . '/some/path/to/the/directory1/exists'],
                [$magentoRoot . '/some/path/to/the/directory2/does/not/exist']
            )
            ->willReturnOnConsecutiveCalls(true, false);

        $this->fileMock->expects($this->once())
            ->method('createDirectory')
            ->with($rootInitDir . '/some/path/to/the/directory1/exists');

        $this->fileMock->expects($this->once())
            ->method('copyDirectory')
            ->withConsecutive([
                $magentoRoot . '/some/path/to/the/directory1/exists',
                $rootInitDir . '/some/path/to/the/directory1/exists',
            ]);

        $this->process->execute();
    }

    /**
     * @param bool $skipCoppingViewPreprocessed
     * @dataProvider executeWithoutWritableDirsDataProvider
     */
    public function testExecuteWithoutWritableDirs(bool $skipCoppingViewPreprocessed)
    {
        $magentoRoot = 'magento_root';
        $rootInitDir = 'magento_root/init';

        $this->loggerMock->expects($this->once())
            ->method('info')
            ->with('Copying writable directories to temp directory.');

        $this->directoryListMock->expects($this->once())
            ->method('getWritableDirectories')
            ->willReturn([]);

        $this->stageConfigMock->expects($this->once())
            ->method('get')
            ->willReturn($skipCoppingViewPreprocessed);

        $this->directoryListMock->expects($this->once())
            ->method('getMagentoRoot')
            ->willReturn($magentoRoot);

        $this->directoryListMock->expects($this->once())
            ->method('getInit')
            ->willReturn($rootInitDir);

        $this->fileMock->expects($this->never())
            ->method('isExists');

        $this->fileMock->expects($this->never())
            ->method('createDirectory');

        $this->fileMock->expects($this->never())
            ->method('copyDirectory');

        $this->process->execute();
    }

    public function executeWithoutWritableDirsDataProvider()
    {
        return [
            [
                'skipCoppingViewPreprocessed' => true,
            ],
            [
                'skipCoppingViewPreprocessed' => false,
            ]
        ];
    }
}
