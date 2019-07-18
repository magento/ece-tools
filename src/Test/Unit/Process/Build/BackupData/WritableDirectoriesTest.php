<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Unit\Process\Build\BackupData;

use Magento\MagentoCloud\App\Logger;
use Magento\MagentoCloud\App\Logger\Pool as LoggerPool;
use Magento\MagentoCloud\Config\GlobalSection as GlobalConfig;
use Magento\MagentoCloud\Filesystem\DirectoryList;
use Magento\MagentoCloud\Filesystem\Driver\File;
use Magento\MagentoCloud\Process\Build\BackupData\WritableDirectories;
use PHPUnit\Framework\TestCase;
use PHPUnit_Framework_MockObject_MockObject as Mock;

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
     * @var DirectoryList|Mock
     */
    private $directoryListMock;

    /**
     * @var GlobalConfig|Mock
     */
    private $globalConfigMock;

    /**
     * @var Logger|Mock
     */
    private $loggerMock;

    /**
     * @var LoggerPool|Mock
     */
    private $loggerPoolMock;

    /**
     * @var string
     */
    private $viewPreprocessedDir = 'var/view_preprocessed';

    /**
     * @var string
     */
    private $logDir = 'var/log';

    /**
     * @var string
     */
    private $magentoRootDir = 'magento_root';

    /**
     * @var string
     */
    private $rootInitDir = 'magento_root/init';

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        $this->fileMock = $this->createMock(File::class);
        $this->directoryListMock = $this->createMock(DirectoryList::class);
        $this->globalConfigMock = $this->createMock(GlobalConfig::class);
        $this->loggerMock = $this->createMock(Logger::class);
        $this->loggerPoolMock = $this->createMock(LoggerPool::class);

        $this->process = new WritableDirectories(
            $this->fileMock,
            $this->directoryListMock,
            $this->globalConfigMock,
            $this->loggerMock,
            $this->loggerPoolMock
        );

        $this->directoryListMock->expects($this->once())
            ->method('getMagentoRoot')
            ->willReturn($this->magentoRootDir);

        $this->directoryListMock->expects($this->exactly(3))
            ->method('getPath')
            ->withConsecutive(
                [DirectoryList::DIR_INIT],
                [DirectoryList::DIR_VIEW_PREPROCESSED],
                [DirectoryList::DIR_LOG]
            )
            ->willReturnOnConsecutiveCalls(
                $this->rootInitDir,
                $this->viewPreprocessedDir,
                $this->logDir
            );

        $this->directoryListMock->expects($this->once())
            ->method('getWritableDirectories')
            ->willReturn([
                'some/path/1',
                $this->viewPreprocessedDir,
                $this->logDir,
                'some/path/2',
            ]);
    }

    public function testExecuteCopyingViewPreprocessed()
    {
        $this->loggerMock->expects($this->once())
            ->method('info')
            ->with(sprintf('Copying writable directories to %s/ directory.', $this->rootInitDir));
        $this->loggerMock->expects($this->exactly(3))
            ->method('debug')
            ->withConsecutive(
                [
                    sprintf(
                        'Copying %s/some/path/1->%s/some/path/1',
                        $this->magentoRootDir,
                        $this->rootInitDir
                    ),
                ],
                [
                    sprintf(
                        'Copying %s->%s',
                        $this->magentoRootDir . '/' . $this->viewPreprocessedDir,
                        $this->rootInitDir . '/' . $this->viewPreprocessedDir
                    ),
                ],
                [
                    sprintf(
                        'Copying %s->%s',
                        $this->magentoRootDir . '/' . $this->logDir,
                        $this->rootInitDir . '/' . $this->logDir
                    ),
                ]
            );

        $this->fileMock->expects($this->exactly(3))
            ->method('isExists')
            ->withConsecutive(
                [$this->magentoRootDir . '/some/path/1'],
                [$this->magentoRootDir . '/' . $this->viewPreprocessedDir],
                [$this->magentoRootDir . '/some/path/2']
            )
            ->willReturnOnConsecutiveCalls(true, true, false);

        $this->loggerMock->expects($this->once())
            ->method('notice')
            ->with('Directory magento_root/some/path/2 does not exist.');

        $this->globalConfigMock->expects($this->once())
            ->method('get')
            ->willReturn(false);

        $this->fileMock->expects($this->exactly(3))
            ->method('createDirectory')
            ->withConsecutive(
                [$this->rootInitDir . '/some/path/1'],
                [$this->rootInitDir . '/' . $this->viewPreprocessedDir],
                [$this->rootInitDir . '/' . $this->logDir]
            );
        $this->fileMock->expects($this->exactly(3))
            ->method('copyDirectory')
            ->withConsecutive(
                [$this->magentoRootDir . '/some/path/1', $this->rootInitDir . '/some/path/1'],
                [
                    $this->magentoRootDir . '/' . $this->viewPreprocessedDir,
                    $this->rootInitDir . '/' . $this->viewPreprocessedDir
                ],
                [$this->magentoRootDir . '/' . $this->logDir, $this->rootInitDir . '/' . $this->logDir]
            );

        $this->loggerPoolMock->expects($this->once())
            ->method('getHandlers')
            ->willReturn(['handler1', 'handler2']);
        $this->loggerMock->expects($this->exactly(2))
            ->method('setHandlers')
            ->withConsecutive(
                [[]],
                [['handler1', 'handler2']]
            );

        $this->process->execute();
    }

    public function testExecuteSkipCopyingViewPreprocessed()
    {
        $this->loggerMock->expects($this->once())
            ->method('info')
            ->with(sprintf('Copying writable directories to %s/ directory.', $this->rootInitDir));
        $this->loggerMock->expects($this->exactly(2))
            ->method('debug')
            ->withConsecutive(
                [
                    sprintf(
                        'Copying %s/some/path/1->%s/some/path/1',
                        $this->magentoRootDir,
                        $this->rootInitDir
                    )
                ],
                [
                    sprintf(
                        'Copying %s->%s',
                        $this->magentoRootDir . '/' . $this->logDir,
                        $this->rootInitDir . '/' . $this->logDir
                    )
                ]
            );

        $this->fileMock->expects($this->exactly(3))
            ->method('isExists')
            ->withConsecutive(
                [$this->magentoRootDir . '/some/path/1'],
                [$this->magentoRootDir . '/' . $this->viewPreprocessedDir],
                [$this->magentoRootDir . '/some/path/2']
            )
            ->willReturnOnConsecutiveCalls(true, true, false);

        $this->loggerMock->expects($this->exactly(2))
            ->method('notice')
            ->withConsecutive(
                [
                    sprintf(
                        'Skip copying %s->%s',
                        $this->magentoRootDir . '/' . $this->viewPreprocessedDir,
                        $this->rootInitDir . '/' . $this->viewPreprocessedDir
                    )
                ],
                ['Directory magento_root/some/path/2 does not exist.']
            );

        $this->globalConfigMock->expects($this->once())
            ->method('get')
            ->willReturn(true);

        $this->fileMock->expects($this->exactly(2))
            ->method('createDirectory')
            ->withConsecutive(
                [$this->rootInitDir . '/some/path/1'],
                [$this->rootInitDir . '/' . $this->logDir]
            );
        $this->fileMock->expects($this->exactly(2))
            ->method('copyDirectory')
            ->withConsecutive(
                [$this->magentoRootDir . '/some/path/1', $this->rootInitDir . '/some/path/1'],
                [$this->magentoRootDir . '/' . $this->logDir, $this->rootInitDir . '/' . $this->logDir]
            );

        $this->loggerPoolMock->expects($this->once())
            ->method('getHandlers')
            ->willReturn(['handler1', 'handler2']);
        $this->loggerMock->expects($this->exactly(2))
            ->method('setHandlers')
            ->withConsecutive(
                [[]],
                [['handler1', 'handler2']]
            );

        $this->process->execute();
    }
}
