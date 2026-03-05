<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Test\Unit\DB;

use Magento\MagentoCloud\App\GenericException;
use Magento\MagentoCloud\DB\Data\ConnectionInterface;
use Magento\MagentoCloud\DB\DumpGenerator;
use Magento\MagentoCloud\DB\DumpInterface;
use Magento\MagentoCloud\Filesystem\DirectoryList;
use Magento\MagentoCloud\Shell\ShellInterface;
use phpmock\phpunit\PHPMock;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * @inheritdoc
 */
#[AllowMockObjectsWithoutExpectations]
class DumpGeneratorTest extends TestCase
{
    use PHPMock;

    /**
     * Mock time() function which is used as part of file name
     *
     * @var integer
     */
    private $time = 123456;

    /**
     * @var DumpGenerator
     */
    private $dumpGenerator;

    /**
     * @var DumpInterface|MockObject
     */
    private $dumpMock;

    /**
     * @var LoggerInterface|MockObject
     */
    private $loggerMock;

    /**
     * @var ShellInterface|MockObject
     */
    private $shellMock;

    /**
     * @var DirectoryList|MockObject
     */
    private $directoryListMock;

    /**
     * @var string
     */
    private $tmpDir;

    /**
     * @var ConnectionInterface|MockObject
     */
    private $connectionDataMock;

    /**
     * Setup the test environment.
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->dumpMock           = $this->createMock(DumpInterface::class);
        $this->loggerMock         = $this->createMock(LoggerInterface::class);
        $this->shellMock          = $this->createMock(ShellInterface::class);
        $this->directoryListMock  = $this->createMock(DirectoryList::class);
        $this->connectionDataMock = $this->createMock(ConnectionInterface::class);
        $this->tmpDir             = sys_get_temp_dir();

        $this->directoryListMock->expects($this->any())
            ->method('getVar')
            ->willReturn($this->tmpDir);

        $timeMock = $this->getFunctionMock('Magento\MagentoCloud\DB', 'time');
        $timeMock->expects($this->any())
            ->willReturn($this->time);

        self::defineFunctionMock('Magento\MagentoCloud\DB', 'fopen');
        self::defineFunctionMock('Magento\MagentoCloud\DB', 'flock');

        $this->dumpGenerator = new DumpGenerator(
            $this->dumpMock,
            $this->loggerMock,
            $this->shellMock,
            $this->directoryListMock
        );
    }

    /**
     * Tear down the test environment.
     *
     * @return void
     */
    protected function tearDown(): void
    {
        if (file_exists($this->tmpDir . '/dbdump.lock')) {
            unlink($this->tmpDir . '/dbdump.lock');
        }
        parent::tearDown();
    }

    /**
     * Test create method.
     *
     * @param bool $removeDefiners
     * @throws GenericException
     * @dataProvider getCreateDataProvider
     * @return void
     */
    #[DataProvider('getCreateDataProvider')]
    public function testCreate(bool $removeDefiners): void
    {
        $dumpFilePath = $this->getDumpFilePath('main');
        $series = [
            'Waiting for lock on db dump.',
            'Start creation DB dump for main database...',
            'Finished DB dump for main database, it can be found here: ' . $dumpFilePath
        ];
        $this->loggerMock->expects($this->exactly(3))
            ->method('info')
            // withConsecutive() alternative.
            ->willReturnCallback(function ($args) use (&$series) {
                $expectedArgs = array_shift($series);
                $this->assertSame($expectedArgs, $args);
            });
        $dumpCommand = $this->getDumpCommand('main');
        $this->dumpMock->expects($this->once())
            ->method('getCommand')
            ->with($this->connectionDataMock)
            ->willReturn($dumpCommand);
        $this->shellMock->expects($this->once())
            ->method('execute')
            ->with($this->addWrapperToRun(
                $dumpCommand,
                $dumpFilePath,
                $removeDefiners
            ));
        $this->dumpGenerator->create('main', $this->connectionDataMock, $removeDefiners, '');
    }

    /**
     * Data provider for testCreate method.
     *
     * @return array
     */
    public static function getCreateDataProvider(): array
    {
        return [
            'without definers' => [true],
            'with definers' => [false],
        ];
    }

    /**
     * Test create with exception method.
     *
     * @return void
     * @throws GenericException
     */
    public function testCreateWithException(): void
    {
        $dumpCommand = $this->getDumpCommand('main');
        $this->loggerMock->expects($this->exactly(2))
            ->method('info')
            // withConsecutive() alternative.
            ->willReturnCallback(function ($args) {
                static $series = [
                    'Waiting for lock on db dump.',
                    'Start creation DB dump for main database...'
                ];
                $expectedArgs = array_shift($series);
                $this->assertSame($expectedArgs, $args);
            });
        $this->dumpMock->expects($this->once())
            ->method('getCommand')
            ->with($this->connectionDataMock)
            ->willReturn($dumpCommand);
        $errorMessage = 'Some error';
        $exception = new \Exception($errorMessage);
        $this->shellMock->expects($this->once())
            ->method('execute')
            ->with($this->addWrapperToRun(
                $dumpCommand,
                $this->getDumpFilePath('main'),
                false
            ))
            ->willThrowException($exception);
        $this->expectExceptionObject($exception);
        $this->dumpGenerator->create('main', $this->connectionDataMock, false, '');
    }

    /**
     * Test failed creation lock file method.
     *
     * @return void
     */
    public function testFailedCreationLockFile(): void
    {
        // Mock fopen() function which is used for creation lock file
        $fopenMock = $this->getFunctionMock('Magento\MagentoCloud\DB', 'fopen');
        $fopenMock->expects($this->once())
            ->willReturn(false);
        $this->loggerMock->expects($this->once())
            ->method('info')
            ->with('Waiting for lock on db dump.');
        $this->loggerMock->expects($this->once())
            ->method('error')
            ->with('Could not get the lock file!');
        $this->shellMock->expects($this->never())
            ->method('execute');
        $this->dumpGenerator->create('main', $this->connectionDataMock, false, '');
    }

    /**
     * Test locked file method.
     *
     * @return void
     */
    public function testLockedFile(): void
    {
        // Mock fopen() function which is used for creation lock file
        $fopenMock = $this->getFunctionMock('Magento\MagentoCloud\DB', 'flock');
        $fopenMock->expects($this->once())
            ->willReturn(false);
        $this->loggerMock->expects($this->exactly(2))
            ->method('info')
            // withConsecutive() alternative.
            ->willReturnCallback(function ($args) {
                static $series = [
                    'Waiting for lock on db dump.',
                    'Dump process is locked!'
                ];
                $expectedArgs = array_shift($series);
                $this->assertSame($expectedArgs, $args);
            });
        $this->shellMock->expects($this->never())
            ->method('execute');
        $this->dumpGenerator->create('main', $this->connectionDataMock, false, '');
    }

    /**
     * Get dump file path method.
     *
     * @param string $type
     * @return string
     */
    private function getDumpFilePath(string $type): string
    {
        return $this->tmpDir . '/dump-' . $type . '-' . $this->time . '.sql.gz';
    }

    /**
     * Get dump command method.
     *
     * @param string $type
     * @return string
     */
    private function getDumpCommand(string $type): string
    {
        return 'cli command for dump db by ' . $type . ' connection';
    }

    /**
     * Add wrapper to run method.
     *
     * @param string $command
     * @param string $dumpFilePath
     * @param bool $removeDefiners
     * @return string
     */
    private function addWrapperToRun(string $command, string $dumpFilePath, $removeDefiners = false): string
    {
        $command = 'bash -c "set -o pipefail; timeout 3600 ' . $command;
        if ($removeDefiners) {
            $command .= ' | sed -e \'s/DEFINER[ ]*=[ ]*[^*]*\*/\*/\'';
        }
        return $command . ' | gzip > ' . $dumpFilePath . '"';
    }
}
