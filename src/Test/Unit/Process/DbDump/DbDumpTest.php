<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Unit\Process\DbDump;

use Magento\MagentoCloud\DB\Data\ConnectionInterface;
use Magento\MagentoCloud\Process\DbDump\DbDump;
use Magento\MagentoCloud\Shell\ShellInterface;
use Psr\Log\LoggerInterface;
use PHPUnit\Framework\TestCase;
use PHPUnit_Framework_MockObject_MockObject as Mock;

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
     * @var ConnectionInterface
     */
    private $connectionDataMock;

    /**
     * @var LoggerInterface|Mock
     */
    private $loggerMock;

    /**
     * @var ShellInterface|Mock
     */
    private $shellMock;

    /**
     * Dump file path
     *
     * @var string
     */
    private $dumpFilePath;

    /**
     * Setup the test environment.
     */
    protected function setUp()
    {
        $this->connectionDataMock = $this->getMockBuilder(ConnectionInterface::class)
            ->getMockForAbstractClass();
        $this->loggerMock = $this->getMockBuilder(LoggerInterface::class)
            ->getMockForAbstractClass();
        $this->shellMock = $this->getMockBuilder(ShellInterface::class)
            ->getMockForAbstractClass();

        // Mock time() function which is used as part of file name
        $temporaryDirectory = sys_get_temp_dir();
        $time = 123456;
        $this->dumpFilePath = $temporaryDirectory . '/dump-' . $time . '.sql.gz';

        $timeMock = $this->getFunctionMock('Magento\MagentoCloud\Process\DbDump', 'time');
        $timeMock->expects($this->once())
            ->willReturn($time);

        $this->process = new DbDump(
            $this->connectionDataMock,
            $this->loggerMock,
            $this->shellMock
        );
    }

    /**
     * @param string $host
     * @param int $port
     * @param string $dbName
     * @param string $user
     * @param string|null $password
     * @param string $expectedCommand
     *
     * @dataProvider executeDataProvider
     */
    public function testExecute($host, $port, $dbName, $user, $password, $expectedCommand)
    {
        $this->loggerMock->expects($this->exactly(3))
            ->method('info')
            ->withConsecutive(
                ['Waiting for lock on db dump.'],
                ['Start creation DB dump...'],
                ['Finished DB dump, it can be found here: ' . $this->dumpFilePath]
            );

        $this->setConnectionData($host, $port, $dbName, $user, $password);

        $command = 'bash -c "set -o pipefail; ' . $expectedCommand . ' | gzip > ' . $this->dumpFilePath . '"';
        $this->shellMock->expects($this->once())
            ->method('execute')
            ->with($command);

        $this->process->execute();

    }

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

        $this->setConnectionData();
        $this->shellMock->expects($this->once())
            ->method('execute')
            ->willThrowException(new \Exception($errorMessage));

        $this->process->execute();
    }

    /**
     * Data provider for testExecute
     * @return array
     */
    public function executeDataProvider()
    {
        $command = 'timeout 3600 mysqldump %s --single-transaction --no-autocommit --quick';
        return [
            [
                'localhost',
                '3306',
                'main',
                'user',
                null,
                sprintf($command, '-h localhost -P 3306 -u user main')
            ],
            [
                'localhost',
                '3306',
                'main',
                'user',
                'pswd',
                sprintf($command, '-h localhost -P 3306 -u user -ppswd main')
            ]
        ];
    }

    /**
     * @param string $host
     * @param string $port
     * @param string $dbName
     * @param string $user
     * @param null|string $password
     */
    private function setConnectionData(
        $host = 'localhost',
        $port = '3306',
        $dbName = 'main',
        $user = 'user',
        $password = null
    ) {
        $this->connectionDataMock->expects($this->once())
            ->method('getHost')
            ->willReturn($host);
        $this->connectionDataMock->expects($this->once())
            ->method('getPort')
            ->willReturn($port);
        $this->connectionDataMock->expects($this->once())
            ->method('getDbName')
            ->willReturn($dbName);
        $this->connectionDataMock->expects($this->once())
            ->method('getUser')
            ->willReturn($user);
        $this->connectionDataMock->expects($this->once())
            ->method('getPassword')
            ->willReturn($password);
    }
}
