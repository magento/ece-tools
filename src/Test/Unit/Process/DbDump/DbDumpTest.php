<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Unit\Process\DbDump;

use Magento\MagentoCloud\DB\DumpInterface;
use Magento\MagentoCloud\Filesystem\DirectoryList;
use Magento\MagentoCloud\Process\DbDump\DbDump;
use Magento\MagentoCloud\Shell\ResultInterface;
use Magento\MagentoCloud\Shell\ShellInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * @inheritdoc
 */
class DbDumpTest extends TestCase
{
    use \phpmock\phpunit\PHPMock;

    /**
     * @var DbDump
     */
    private $process;

    /**
     * @var DumpInterface|MockObject
     */
    private $dbDumpMock;

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
     * Dump file path
     *
     * @var string
     */
    private $dumpFilePath;

    /**
     * @var string
     */
    private $tmpDir;

    /**
     * Setup the test environment.
     */
    protected function setUp()
    {
        $this->dbDumpMock = $this->getMockForAbstractClass(DumpInterface::class);
        $this->loggerMock = $this->getMockForAbstractClass(LoggerInterface::class);
        $this->shellMock = $this->getMockForAbstractClass(ShellInterface::class);
        $this->directoryListMock = $this->createMock(DirectoryList::class);

        $this->tmpDir = sys_get_temp_dir();
        $this->directoryListMock->expects($this->once())
            ->method('getVar')
            ->willReturn($this->tmpDir);

        // Mock time() function which is used as part of file name
        $time = 123456;
        $this->dumpFilePath = $this->tmpDir . '/dump-' . $time . '.sql.gz';

        $timeMock = $this->getFunctionMock('Magento\MagentoCloud\Process\DbDump', 'time');
        $timeMock->expects($this->once())
            ->willReturn($time);

        self::defineFunctionMock('Magento\MagentoCloud\Process\DbDump', 'fopen');
        self::defineFunctionMock('Magento\MagentoCloud\Process\DbDump', 'flock');

        $this->process = new DbDump(
            $this->dbDumpMock,
            $this->loggerMock,
            $this->shellMock,
            $this->directoryListMock
        );
    }

    protected function tearDown()
    {
        if (file_exists($this->tmpDir . '/dbdump.lock')) {
            unlink($this->tmpDir . '/dbdump.lock');
        }
        parent::tearDown();
    }

    private function getCommand()
    {
        $command = 'mysqldump -h localhost';
        $this->dbDumpMock->expects($this->once())
            ->method('getCommand')
            ->willReturn($command);

        return 'bash -c "set -o pipefail; timeout 3600 ' . $command . ' | gzip > ' . $this->dumpFilePath . '"';
    }

    /**
     * @throws \Magento\MagentoCloud\Process\ProcessException
     */
    public function testExecute()
    {
        $this->loggerMock->expects($this->exactly(3))
            ->method('info')
            ->withConsecutive(
                ['Waiting for lock on db dump.'],
                ['Start creation DB dump...'],
                ['Finished DB dump, it can be found here: ' . $this->dumpFilePath]
            );

        $command = $this->getCommand();

        $resultMock = $this->getMockForAbstractClass(ResultInterface::class);
        $resultMock->expects($this->once())
            ->method('getExitCode')
            ->willReturn(0);
        $this->shellMock->expects($this->once())
            ->method('execute')
            ->with($command)
            ->willReturn($resultMock);

        $this->process->execute();
    }

    /**
     * @throws \Magento\MagentoCloud\Process\ProcessException
     */
    public function testExecuteWithException()
    {
        $errorMessage = 'Some error';
        $this->loggerMock->expects($this->exactly(2))
            ->method('info')
            ->withConsecutive(
                ['Waiting for lock on db dump.'],
                ['Start creation DB dump...']
            );
        $this->loggerMock->expects($this->once())
            ->method('error')
            ->with($errorMessage);

        $this->getCommand();
        $this->shellMock->expects($this->once())
            ->method('execute')
            ->willThrowException(new \Exception($errorMessage));

        $this->process->execute();
    }

    /**
     * @throws \Magento\MagentoCloud\Process\ProcessException
     */
    public function testFailedCreationLockFile()
    {
        // Mock fopen() function which is used for creation lock file
        $fopenMock = $this->getFunctionMock('Magento\MagentoCloud\Process\DbDump', 'fopen');
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

        $this->process->execute();
    }

    /**
     * @throws \Magento\MagentoCloud\Process\ProcessException
     */
    public function testLockedFile()
    {
        // Mock fopen() function which is used for creation lock file
        $fopenMock = $this->getFunctionMock('Magento\MagentoCloud\Process\DbDump', 'flock');
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

        $this->process->execute();
    }

    /**
     * @throws \Magento\MagentoCloud\Process\ProcessException
     */
    public function testExecuteWithErrors()
    {
        $this->loggerMock->expects($this->exactly(2))
            ->method('info')
            ->withConsecutive(
                ['Waiting for lock on db dump.'],
                ['Start creation DB dump...']
            );
        $this->loggerMock->expects($this->once())
            ->method('error')
            ->with('Error has occurred during mysqldump');

        $command = $this->getCommand();

        $resultMock1 = $this->getMockForAbstractClass(ResultInterface::class);
        $resultMock1->expects($this->once())
            ->method('getExitCode')
            ->willReturn(128);
        $resultMock2 = $this->getMockForAbstractClass(ResultInterface::class);

        $this->shellMock->expects($this->exactly(2))
            ->method('execute')
            ->withConsecutive(
                [$command],
                ['rm ' . $this->dumpFilePath]
            )->willReturnMap([
                [$command, [], $resultMock1],
                ['rm ' . $this->dumpFilePath, [], $resultMock2],
            ]);

        $this->process->execute();
    }
}
