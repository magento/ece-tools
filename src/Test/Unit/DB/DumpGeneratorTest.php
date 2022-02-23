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
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * @inheritdoc
 */
class DumpGeneratorTest extends TestCase
{
    use \phpmock\phpunit\PHPMock;

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
     */
    protected function setUp(): void
    {
        $this->dumpMock = $this->getMockForAbstractClass(DumpInterface::class);
        $this->loggerMock = $this->getMockForAbstractClass(LoggerInterface::class);
        $this->shellMock = $this->getMockForAbstractClass(ShellInterface::class);
        $this->directoryListMock = $this->createMock(DirectoryList::class);
        $this->connectionDataMock = $this->createMock(ConnectionInterface::class);

        $this->tmpDir = sys_get_temp_dir();
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

    protected function tearDown(): void
    {
        if (file_exists($this->tmpDir . '/dbdump.lock')) {
            unlink($this->tmpDir . '/dbdump.lock');
        }
        parent::tearDown();
    }

    /**
     * @param bool $removeDefiners
     * @throws GenericException
     * @dataProvider getCreateDataProvider
     */
    public function testCreate(bool $removeDefiners)
    {
        $dumpFilePath = $this->getDumpFilePath('main');
        $this->loggerMock->expects($this->exactly(3))
            ->method('info')
            ->withConsecutive(
                ['Waiting for lock on db dump.'],
                ['Start creation DB dump for main database...'],
                ['Finished DB dump for main database, it can be found here: ' . $dumpFilePath]
            );
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
     * @return array
     */
    public function getCreateDataProvider(): array
    {
        return [
            'without definers' => [true],
            'with definers' => [false],
        ];
    }

    /**
     * @throws GenericException
     */
    public function testCreateWithException()
    {
        $dumpCommand = $this->getDumpCommand('main');
        $this->loggerMock->expects($this->exactly(2))
            ->method('info')
            ->withConsecutive(
                ['Waiting for lock on db dump.'],
                ['Start creation DB dump for main database...']
            );
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

    public function testFailedCreationLockFile()
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

    public function testLockedFile()
    {
        // Mock fopen() function which is used for creation lock file
        $fopenMock = $this->getFunctionMock('Magento\MagentoCloud\DB', 'flock');
        $fopenMock->expects($this->once())
            ->willReturn(false);
        $this->loggerMock->expects($this->exactly(2))
            ->method('info')
            ->withConsecutive(
                ['Waiting for lock on db dump.'],
                ['Dump process is locked!']
            );
        $this->shellMock->expects($this->never())
            ->method('execute');
        $this->dumpGenerator->create('main', $this->connectionDataMock, false, '');
    }

    private function getDumpFilePath(string $type): string
    {
        return $this->tmpDir . '/dump-' . $type . '-' . $this->time . '.sql.gz';
    }

    private function getDumpCommand(string $type): string
    {
        return 'cli command for dump db by ' . $type . ' connection';
    }

    private function addWrapperToRun(string $command, string $dumpFilePath, $removeDefiners = false): string
    {
        $command = 'bash -c "set -o pipefail; timeout 3600 ' . $command;
        if ($removeDefiners) {
            $command .= ' | sed -e \'s/DEFINER[ ]*=[ ]*[^*]*\*/\*/\'';
        }
        return $command . ' | gzip > ' . $dumpFilePath . '"';
    }
}
