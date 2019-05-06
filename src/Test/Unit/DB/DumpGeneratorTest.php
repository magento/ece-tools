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
use Magento\MagentoCloud\Shell\ShellInterface;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use PHPUnit\Framework\TestCase;

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
     * @param bool $keepDefiners
     * @return string
     */
    private function getCommand(bool $keepDefiners = false): string
    {
        $command = 'mysqldump -h localhost';
        $this->dbDumpMock->expects($this->once())
            ->method('getCommand')
            ->willReturn($command);

        $fullCommand = 'bash -c "set -o pipefail; timeout 3600 ' . $command;
        if (!$keepDefiners) {
            $fullCommand .= ' | sed -e \'s/DEFINER[ ]*=[ ]*[^*]*\*/\*/\'';
        }

        return $fullCommand . ' | gzip > ' . $this->dumpFilePath . '"';
    }

    /**
     * @param bool $keepDefiners
     * @throws \Magento\MagentoCloud\Package\UndefinedPackageException
     * @dataProvider getCreateDataProvider
     */
    public function testCreate(bool $keepDefiners)
    {
        $this->loggerMock->expects($this->exactly(3))
            ->method('info')
            ->withConsecutive(
                ['Waiting for lock on db dump.'],
                ['Start creation DB dump...'],
                ['Finished DB dump, it can be found here: ' . $this->dumpFilePath]
            );

        $command = $this->getCommand($keepDefiners);

        $this->shellMock->expects($this->once())
            ->method('execute')
            ->with($command)
            ->willReturn([]);

        $this->dumpGenerator->create($keepDefiners);
    }

    /**
     * @return array
     */
    public function getCreateDataProvider(): array
    {
        return [
            'without definers' => [false],
            'with definers' => [true],
        ];
    }

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

    public function testCreateWithErrors()
    {
        $createOutput = ['Some error'];
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

        $this->shellMock->expects($this->exactly(2))
            ->method('execute')
            ->withConsecutive(
                [$command],
                ['rm ' . $this->dumpFilePath]
            )->willReturnMap([
                [$command, [], $createOutput],
                ['rm ' . $this->dumpFilePath, [], []],
            ]);

        $this->dumpGenerator->create(false);
    }
}
