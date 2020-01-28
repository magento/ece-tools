<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Test\Unit\DB;

use Magento\MagentoCloud\App\GenericException;
use Magento\MagentoCloud\Config\Database\DbConfig;
use Magento\MagentoCloud\DB\Data\ConnectionFactory;
use Magento\MagentoCloud\DB\Data\ConnectionInterface;
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
     * @var string
     */
    private $tmpDir;

    /**
     * @var DbConfig|MockObject
     */
    private $dbConfigMock;

    /**
     * @var ConnectionFactory|MockObject
     */
    private $connectionFactoryMock;

    /**
     * @var ConnectionInterface|MockObject
     */
    private $connectionDataMock;

    /**
     * Setup the test environment.
     */
    protected function setUp()
    {
        $this->dbDumpMock = $this->getMockForAbstractClass(DumpInterface::class);
        $this->loggerMock = $this->getMockForAbstractClass(LoggerInterface::class);
        $this->shellMock = $this->getMockForAbstractClass(ShellInterface::class);
        $this->directoryListMock = $this->createMock(DirectoryList::class);
        $this->dbConfigMock = $this->createMock(DbConfig::class);
        $this->connectionFactoryMock = $this->createMock(ConnectionFactory::class);
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
            $this->dbDumpMock,
            $this->loggerMock,
            $this->shellMock,
            $this->directoryListMock,
            $this->dbConfigMock,
            $this->connectionFactoryMock
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
     * @throws GenericException
     * @throws \ReflectionException
     * @dataProvider getCreateDataProvider
     */
    public function testCreate(bool $removeDefiners)
    {
        $this->beforeTestByDefault();
        $dumpFilePath = $this->getDumpFilePath('main');
        $this->loggerMock->expects($this->exactly(3))
            ->method('info')
            ->withConsecutive(
                ['Waiting for lock on db dump.'],
                ['Start creation DB dump for main database...'],
                ['Finished DB dump for main database, it can be found here: ' . $dumpFilePath]
            );
        $dumpCommand = $this->getDumpCommand('main');
        $this->dbDumpMock->expects($this->once())
            ->method('getCommand')
            ->with($this->connectionDataMock)
            ->willReturn($dumpCommand);
        $processMock = $this->getMockForAbstractClass(ProcessInterface::class);
        $processMock->expects($this->once())
            ->method('getExitCode')
            ->willReturn(0);
        $this->shellMock->expects($this->once())
            ->method('execute')
            ->with($this->addWrapperToRun(
                $dumpCommand,
                $dumpFilePath,
                $removeDefiners
            ))
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
     * @throws GenericException
     */
    public function testCreateWithException()
    {
        $this->beforeTestByDefault();
        $this->loggerMock->expects($this->exactly(2))
            ->method('info')
            ->withConsecutive(
                ['Waiting for lock on db dump.'],
                ['Start creation DB dump for main database...']
            );
        $dumpCommand = $this->getDumpCommand('main');
        $this->dbDumpMock->expects($this->once())
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
        $this->dumpGenerator->create(false);
    }

    public function testFailedCreationLockFile()
    {
        $this->beforeTestByDefault();
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
        $this->beforeTestByDefault();
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

    public function testCreateWithErrors()
    {
        $this->beforeTestByDefault();
        $this->loggerMock->expects($this->exactly(2))
            ->method('info')
            ->withConsecutive(
                ['Waiting for lock on db dump.'],
                ['Start creation DB dump for main database...']
            );
        $this->loggerMock->expects($this->once())
            ->method('error')
            ->with('Error has occurred during mysqldump');
        $dumpFilePath = $this->getDumpFilePath('main');
        $dumpCommand = $this->getDumpCommand('main');
        $this->dbDumpMock->expects($this->once())
            ->method('getCommand')
            ->with($this->connectionDataMock)
            ->willReturn($dumpCommand);

        $processMock1 = $this->getMockForAbstractClass(ProcessInterface::class);
        $processMock1->expects($this->once())
            ->method('getExitCode')
            ->willReturn(128);
        $processMock2 = $this->getMockForAbstractClass(ProcessInterface::class);

        $this->shellMock->expects($this->exactly(2))
            ->method('execute')
            ->willReturnMap([
                [$this->addWrapperToRun($dumpCommand, $dumpFilePath), [], $processMock1],
                ['rm ' . $dumpFilePath, [], $processMock2],
            ]);

        $this->dumpGenerator->create(false);
    }

    public function testCreateWithSplitDbDyDefault()
    {
        $this->beforeTestWithSplitDbByDefault();

        $mainDumpFilePath = $this->getDumpFilePath('main');
        $quoteDumpFilePath = $this->getDumpFilePath('quote');
        $salesDumpFilePath = $this->getDumpFilePath('sales');

        $this->loggerMock->expects($this->exactly(9))
            ->method('info')
            ->withConsecutive(
                ['Waiting for lock on db dump.'],
                ['Start creation DB dump for main database...'],
                ['Finished DB dump for main database, it can be found here: ' . $mainDumpFilePath],
                ['Waiting for lock on db dump.'],
                ['Start creation DB dump for quote database...'],
                ['Finished DB dump for quote database, it can be found here: ' . $quoteDumpFilePath],
                ['Waiting for lock on db dump.'],
                ['Start creation DB dump for sales database...'],
                ['Finished DB dump for sales database, it can be found here: ' . $salesDumpFilePath]
            );

        $mainDumpCommand = $this->getDumpCommand('main');
        $quoteDumpCommand = $this->getDumpCommand('quote');
        $salesDumpCommand = $this->getDumpCommand('sales');

        $this->dbDumpMock->expects($this->exactly(3))
            ->method('getCommand')
            ->with($this->connectionDataMock)
            ->willReturnOnConsecutiveCalls(
                $mainDumpCommand,
                $quoteDumpCommand,
                $salesDumpCommand
            );

        $processMock = $this->getMockForAbstractClass(ProcessInterface::class);
        $processMock->expects($this->exactly(3))
            ->method('getExitCode')
            ->willReturn(0);
        $this->shellMock->expects($this->exactly(3))
            ->method('execute')
            ->withConsecutive(
                [$this->addWrapperToRun($mainDumpCommand, $mainDumpFilePath)],
                [$this->addWrapperToRun($quoteDumpCommand, $quoteDumpFilePath)],
                [$this->addWrapperToRun($salesDumpCommand, $salesDumpFilePath)]
            )
            ->willReturn($processMock);

        $this->dumpGenerator->create(false);
    }

    public function testCreateWithInvalidDbNames()
    {
        $this->loggerMock->expects($this->once())
            ->method('error')
            ->with(
                'Incorrect the database names:[ dbname1, dbname2, dbname3 ].'
                . ' Available database names: [ main, quote, sales ]'
            );
        $this->dbConfigMock->expects($this->never())
            ->method('get');
        $this->shellMock->expects($this->never())
            ->method('execute');
        $this->dumpGenerator->create(false, ['dbname1', 'main', 'dbname2', 'sales', 'dbname3', 'quote']);
    }

    public function testWithUnavailabilityConnections()
    {
        $this->dbConfigMock->expects($this->once())
            ->method('get')
            ->willReturn([
                'connection' => [
                    'default' => [],
                    'indexer' => [],
                ]
            ]);
        $this->loggerMock->expects($this->exactly(2))
            ->method('error')
            ->withConsecutive(
                ['Environment has not connection `checkout` associated with database `quote`'],
                ['Environment has not connection `sales` associated with database `sales`']
            );
        $this->shellMock->expects($this->never())
            ->method('execute');
        $this->dumpGenerator->create(false, ['main', 'sales', 'quote']);
    }

    public function testCreateWithSplitDbAndUserDatabases()
    {
        $this->dbConfigMock->expects($this->once())
            ->method('get')
            ->willReturn([
                'connection' => [
                    'default' => [],
                    'indexer' => [],
                    'checkout' => []
                ]
            ]);

        $this->connectionFactoryMock->expects($this->exactly(2))
            ->method('create')
            ->withConsecutive(
                ['slave'],
                ['quote-slave']
            )
            ->willReturn($this->connectionDataMock);

        $mainDumpFilePath = $this->getDumpFilePath('main');
        $quoteDumpFilePath = $this->getDumpFilePath('quote');

        $this->loggerMock->expects($this->exactly(6))
            ->method('info')
            ->withConsecutive(
                ['Waiting for lock on db dump.'],
                ['Start creation DB dump for main database...'],
                ['Finished DB dump for main database, it can be found here: ' . $mainDumpFilePath],
                ['Waiting for lock on db dump.'],
                ['Start creation DB dump for quote database...'],
                ['Finished DB dump for quote database, it can be found here: ' . $quoteDumpFilePath]
            );

        $mainDumpCommand = $this->getDumpCommand('main');
        $quoteDumpCommand = $this->getDumpCommand('quote');

        $this->dbDumpMock->expects($this->exactly(2))
            ->method('getCommand')
            ->with($this->connectionDataMock)
            ->willReturnOnConsecutiveCalls(
                $mainDumpCommand,
                $quoteDumpCommand
            );

        $processMock = $this->getMockForAbstractClass(ProcessInterface::class);
        $processMock->expects($this->exactly(2))
            ->method('getExitCode')
            ->willReturn(0);
        $this->shellMock->expects($this->exactly(2))
            ->method('execute')
            ->withConsecutive(
                [$this->addWrapperToRun($mainDumpCommand, $mainDumpFilePath)],
                [$this->addWrapperToRun($quoteDumpCommand, $quoteDumpFilePath)]
            )
            ->willReturn($processMock);

        $this->dumpGenerator->create(false, ['main', 'quote']);
    }

    private function beforeTestByDefault()
    {
        $this->dbConfigMock->expects($this->once())
            ->method('get')
            ->willReturn([
                'connection' => [
                    'default' => [],
                    'indexer' => [],
                ]
            ]);

        $this->connectionFactoryMock->expects($this->once())
            ->method('create')
            ->with('slave')
            ->willReturn($this->connectionDataMock);
    }

    private function beforeTestWithSplitDbByDefault()
    {
        $this->dbConfigMock->expects($this->once())
            ->method('get')
            ->willReturn([
                'connection' => [
                    'default' => [],
                    'indexer' => [],
                    'checkout' => [],
                    'sales' => [],
                ]
            ]);

        $this->connectionFactoryMock->expects($this->exactly(3))
            ->method('create')
            ->withConsecutive(
                ['slave'],
                ['quote-slave'],
                ['sales-slave']
            )
            ->willReturn($this->connectionDataMock);
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
