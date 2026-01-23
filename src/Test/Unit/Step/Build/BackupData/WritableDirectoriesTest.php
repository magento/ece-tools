<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Test\Unit\Step\Build\BackupData;

use Magento\MagentoCloud\App\Error;
use Magento\MagentoCloud\App\Logger;
use Magento\MagentoCloud\App\Logger\Pool as LoggerPool;
use Magento\MagentoCloud\App\LoggerException;
use Magento\MagentoCloud\Config\GlobalSection as GlobalConfig;
use Magento\MagentoCloud\Filesystem\DirectoryList;
use Magento\MagentoCloud\Filesystem\Driver\File;
use Magento\MagentoCloud\Filesystem\FileSystemException;
use Magento\MagentoCloud\Step\Build\BackupData\WritableDirectories;
use Magento\MagentoCloud\Step\StepException;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @inheritdoc
 */
#[AllowMockObjectsWithoutExpectations]
class WritableDirectoriesTest extends TestCase
{
    /**
     * @var WritableDirectories
     */
    public $step;

    /**
     * @var File|MockObject
     */
    private $fileMock;

    /**
     * @var DirectoryList|MockObject
     */
    private $directoryListMock;

    /**
     * @var GlobalConfig|MockObject
     */
    private $globalConfigMock;

    /**
     * @var Logger|MockObject
     */
    private $loggerMock;

    /**
     * @var LoggerPool|MockObject
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
    protected function setUp(): void
    {
        $this->fileMock = $this->createMock(File::class);
        $this->directoryListMock = $this->createMock(DirectoryList::class);
        $this->globalConfigMock = $this->createMock(GlobalConfig::class);
        $this->loggerMock = $this->createMock(Logger::class);
        $this->loggerPoolMock = $this->createMock(LoggerPool::class);

        $this->directoryListMock->expects($this->once())
            ->method('getMagentoRoot')
            ->willReturn($this->magentoRootDir);
        $this->directoryListMock->expects($this->exactly(3))
            ->method('getPath')
            // withConsecutive() alternative.
            ->willReturnCallback(fn($param) => match ([$param]) {
                [DirectoryList::DIR_INIT] => $this->rootInitDir,
                [DirectoryList::DIR_VIEW_PREPROCESSED] => $this->viewPreprocessedDir,
                [DirectoryList::DIR_LOG] => $this->logDir
            });
        $this->directoryListMock->expects($this->once())
            ->method('getWritableDirectories')
            ->willReturn([
                'some/path/1',
                $this->viewPreprocessedDir,
                $this->logDir,
                'some/path/2',
            ]);

        $this->step = new WritableDirectories(
            $this->fileMock,
            $this->directoryListMock,
            $this->globalConfigMock,
            $this->loggerMock,
            $this->loggerPoolMock
        );
    }

    /**
     * @throws StepException
     */
    public function testExecuteWithLoggerException()
    {
        $this->loggerPoolMock->expects($this->once())
            ->method('getHandlers')
            ->willThrowException(new LoggerException('some error'));

        $this->expectExceptionMessage('some error');
        $this->expectException(StepException::class);
        $this->expectExceptionCode(Error::BUILD_UNABLE_TO_CREATE_LOGGER);

        $this->step->execute();
    }

    /**
     * @throws StepException
     */
    public function testExecuteWithFileSystemException()
    {
        $this->fileMock->expects($this->any())
            ->method('copyDirectory')
            ->willThrowException(new FileSystemException('some error'));

        $this->expectExceptionMessage('some error');
        $this->expectException(StepException::class);
        $this->expectExceptionCode(Error::BUILD_WRITABLE_DIRECTORY_COPYING_FAILED);

        $this->step->execute();
    }

    /**
     * @throws StepException
     */
    public function testExecuteCopyingViewPreprocessed(): void
    {
        $this->loggerMock->expects($this->once())
            ->method('info')
            ->with(sprintf('Copying writable directories to %s/ directory.', $this->rootInitDir));
        $this->loggerMock->expects($this->exactly(3))
            ->method('debug')
            // withConsecutive() alternative.
            ->with(self::callback(function (string $message) {
                static $i = 0;
                return match (++$i) {
                    1 => $message === sprintf(
                        'Copying %s/some/path/1->%s/some/path/1',
                        $this->magentoRootDir,
                        $this->rootInitDir
                    ),
                    2 => $message === sprintf(
                        'Copying %s->%s',
                        $this->magentoRootDir . '/' . $this->viewPreprocessedDir,
                        $this->rootInitDir . '/' . $this->viewPreprocessedDir
                    ),
                    3 => $message === sprintf(
                        'Copying %s->%s',
                        $this->magentoRootDir . '/' . $this->logDir,
                        $this->rootInitDir . '/' . $this->logDir
                    ),
                };
            }));
        $this->fileMock->expects($this->exactly(3))
            ->method('isExists')
            // withConsecutive() alternative.
            ->willReturnCallback(fn($param) => match ([$param]) {
                [$this->magentoRootDir . '/some/path/1'] => true,
                [$this->magentoRootDir . '/' . $this->viewPreprocessedDir] => true,
                [$this->magentoRootDir . '/some/path/2'] => false
            });
        $this->loggerMock->expects($this->once())
            ->method('notice')
            ->with('Directory magento_root/some/path/2 does not exist.');
        $this->globalConfigMock->expects($this->once())
            ->method('get')
            ->willReturn(false);
        $this->fileMock->expects($this->exactly(3))
            ->method('createDirectory')
            // withConsecutive() alternative.
            ->with(self::callback(function (string $message) {
                static $i = 0;
                return match (++$i) {
                    1 => $message === $this->rootInitDir . '/some/path/1',
                    2 => $message === $this->rootInitDir . '/' . $this->viewPreprocessedDir,
                    3 => $message === $this->rootInitDir . '/' . $this->logDir
                };
            }));
        $matcher = $this->exactly(3);
        $series = [
            [$this->magentoRootDir . '/some/path/1', $this->rootInitDir . '/some/path/1'],
            [
                $this->magentoRootDir . '/' . $this->viewPreprocessedDir,
                $this->rootInitDir . '/' . $this->viewPreprocessedDir
            ],
            [$this->magentoRootDir . '/' . $this->logDir, $this->rootInitDir . '/' . $this->logDir]
        ];
        $this->fileMock->expects($matcher)
            ->method('copyDirectory')
            // withConsecutive() alternative.
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
        $this->loggerPoolMock->expects($this->once())
            ->method('getHandlers')
            ->willReturn(['handler1', 'handler2']);
        $this->loggerMock->expects($this->exactly(2))
            ->method('setHandlers')
            // withConsecutive() alternative.
            ->with(self::callback(function (array $message) {
                static $i = 0;
                return match (++$i) {
                    1 => $message === [],
                    2 => $message === ['handler1', 'handler2'],
                };
            }));

        $this->step->execute();
    }

    /**
     * @throws StepException
     */
    public function testExecuteSkipCopyingViewPreprocessed(): void
    {
        $this->loggerMock->expects($this->once())
            ->method('info')
            ->with(sprintf('Copying writable directories to %s/ directory.', $this->rootInitDir));
        $this->loggerMock->expects($this->exactly(2))
            ->method('debug')
            // withConsecutive() alternative.
            ->with(self::callback(function (string $message) {
                static $i = 0;
                return match (++$i) {
                    1 => $message === sprintf(
                        'Copying %s/some/path/1->%s/some/path/1',
                        $this->magentoRootDir,
                        $this->rootInitDir
                    ),
                    2 => $message === sprintf(
                        'Copying %s->%s',
                        $this->magentoRootDir . '/' . $this->logDir,
                        $this->rootInitDir . '/' . $this->logDir
                    )
                };
            }));
        $this->fileMock->expects($this->exactly(3))
            ->method('isExists')
            // withConsecutive() alternative.
            ->willReturnCallback(fn($param) => match ([$param]) {
                [$this->magentoRootDir . '/some/path/1'] => true,
                [$this->magentoRootDir . '/' . $this->viewPreprocessedDir] => true,
                [$this->magentoRootDir . '/some/path/2'] => false
            });
        $this->loggerMock->expects($this->exactly(2))
            ->method('notice')
            // withConsecutive() alternative.
            ->with(self::callback(function (string $message) {
                static $i = 0;
                return match (++$i) {
                    1 => $message === sprintf(
                        'Skip copying %s->%s',
                        $this->magentoRootDir . '/' . $this->viewPreprocessedDir,
                        $this->rootInitDir . '/' . $this->viewPreprocessedDir
                    ),
                    2 => $message === 'Directory magento_root/some/path/2 does not exist.'
                };
            }));
        $this->globalConfigMock->expects($this->once())
            ->method('get')
            ->willReturn(true);
        $this->fileMock->expects($this->exactly(2))
            ->method('createDirectory')
            // withConsecutive() alternative.
            ->with(self::callback(function (string $message) {
                static $i = 0;
                return match (++$i) {
                    1 => $message === $this->rootInitDir . '/some/path/1',
                    2 => $message === $this->rootInitDir . '/' . $this->logDir
                };
            }));
        $series = [
            [$this->magentoRootDir . '/some/path/1', $this->rootInitDir . '/some/path/1'],
            [$this->magentoRootDir . '/' . $this->logDir, $this->rootInitDir . '/' . $this->logDir]
        ];
        $matcher = $this->exactly(2);
        $this->fileMock->expects($matcher)
            ->method('copyDirectory')
            // withConsecutive() alternative.
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
        $this->loggerPoolMock->expects($this->once())
            ->method('getHandlers')
            ->willReturn(['handler1', 'handler2']);
        $this->loggerMock->expects($this->exactly(2))
            ->method('setHandlers')
            // withConsecutive() alternative.
            ->with(self::callback(function (array $message) {
                static $i = 0;
                return match (++$i) {
                    1 => $message === [],
                    2 => $message === ['handler1', 'handler2'],
                };
            }));

        $this->step->execute();
    }

    private function resolveInvocations(\PHPUnit\Framework\MockObject\Rule\InvocationOrder $matcher): int
    {
        if (method_exists($matcher, 'numberOfInvocations')) { // PHPUnit 10+ (including PHPUnit 12)
            return $matcher->numberOfInvocations();
        }

        if (method_exists($matcher, 'getInvocationCount')) { // before PHPUnit 10
            return $matcher->getInvocationCount();
        }

        $this->fail('Cannot count the number of invocations.');
    }
}
