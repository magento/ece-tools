<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Test\Unit\DB;

use Magento\MagentoCloud\DB\DumpGenerator;
use Magento\MagentoCloud\DB\DumpInterface;
use Magento\MagentoCloud\Filesystem\DirectoryList;
use Magento\MagentoCloud\Shell\ProcessInterface;
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
     * @var DumpGenerator
     */
    private $dumpGenerator;

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

        $timeMock = $this->getFunctionMock('Magento\MagentoCloud\DB', 'time');
        $timeMock->expects($this->once())
            ->willReturn($time);

        self::defineFunctionMock('Magento\MagentoCloud\DB', 'fopen');
        self::defineFunctionMock('Magento\MagentoCloud\DB', 'flock');

        $this->dumpGenerator = new DumpGenerator(
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

    /**
     * @param bool $removeDefiners
     * @return string
     */
    private function getCommand(bool $removeDefiners = false): string
    {
        $command = 'mysqldump -h localhost';
        $this->dbDumpMock->expects($this->once())
            ->method('getCommand')
            ->willReturn($command);

        $fullCommand = 'bash -c "set -o pipefail; timeout 3600 ' . $command;
        if ($removeDefiners) {
            $fullCommand .= ' | sed -e \'s/DEFINER[ ]*=[ ]*[^*]*\*/\*/\'';
        }

        return $fullCommand . ' | gzip > ' . $this->dumpFilePath . '"';
    }

    /**
     * @param bool $removeDefiners
     * @throws \Magento\MagentoCloud\Package\UndefinedPackageException
     * @dataProvider getCreateDataProvider
     */
    public function testCreate(bool $removeDefiners)
    {
        $this->loggerMock->expects($this->exactly(3))
            ->method('info')
            ->withConsecutive(
                ['Waiting for lock on db dump.'],
                ['Start creation DB dump...'],
                ['Finished DB dump, it can be found here: ' . $this->dumpFilePath]
            );

        $command = $this->getCommand($removeDefiners);

        $processMock = $this->getMockForAbstractClass(ProcessInterface::class);
        $processMock->expects($this->once())
            ->method('getExitCode')
            ->willReturn(0);
        $this->shellMock->expects($this->once())
            ->method('execute')
            ->with($command)
            ->willReturn($processMock);

        $this->dumpGenerator->create($removeDefiners);
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
     * @throws \Magento\MagentoCloud\Process\ProcessException
     */
    public function testCreateWithException()
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

        $this->dumpGenerator->create(false);
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

        $this->dumpGenerator->create(false);
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

        $this->dumpGenerator->create(false);
    }

    /**
     * @throws \Magento\MagentoCloud\Process\ProcessException
     */
    public function testCreateWithErrors()
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

        $processMock1 = $this->getMockForAbstractClass(ProcessInterface::class);
        $processMock1->expects($this->once())
            ->method('getExitCode')
            ->willReturn(128);
        $processMock2 = $this->getMockForAbstractClass(ProcessInterface::class);

        $this->shellMock->expects($this->exactly(2))
            ->method('execute')
            ->withConsecutive(
                [$command],
                ['rm ' . $this->dumpFilePath]
            )->willReturnMap([
                [$command, [], $processMock1],
                ['rm ' . $this->dumpFilePath, [], $processMock2],
            ]);

        $this->dumpGenerator->create(false);
    }
}
