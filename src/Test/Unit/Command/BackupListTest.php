<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Unit\Command;

use Magento\MagentoCloud\Command\BackupList;
use Symfony\Component\Console\Tester\CommandTester;
use PHPUnit\Framework\TestCase;
use Magento\MagentoCloud\Command\Backup\FileList as BackupFilesList;
use Psr\Log\LoggerInterface;
use PHPUnit_Framework_MockObject_MockObject as Mock;

/**
 * @inheritdoc
 */
class BackupListTest extends TestCase
{
    /**
     * @var BackupFilesList|Mock
     */
    private $backupFilesListMock;

    /**
     * @var LoggerInterface|Mock
     */
    private $loggerMock;

    /**
     * @var BackupList
     */
    private $command;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        $this->backupFilesListMock = $this->createMock(BackupFilesList::class);
        $this->loggerMock = $this->getMockBuilder(LoggerInterface::class)
            ->getMockForAbstractClass();

        $this->command = new BackupList($this->backupFilesListMock, $this->loggerMock);
    }

    /**
     * @param array $backupList
     * @param string $output
     * @dataProvider executeDataProvider
     */
    public function testExecute(array $backupList, string $output)
    {
        $this->loggerMock->expects($this->never())
            ->method('critical');
        $this->backupFilesListMock->expects($this->once())
            ->method('get')
            ->willReturn($backupList);
        $tester = new CommandTester($this->command);
        $tester->execute([]);

        $this->assertSame(0, $tester->getStatusCode());
        $this->assertSame($output, $tester->getDisplay());
    }

    /**
     * @return array
     */
    public function executeDataProvider(): array
    {
        return [
            [
                'backupList' => [],
                'output' => 'The list of backup files:' . PHP_EOL . 'There are no files in the backup' . PHP_EOL,
            ],
            [
                'backupList' => ['app/etc/config.php', 'app/etc/env.php'],
                'output' => 'The list of backup files:' . PHP_EOL . 'app/etc/config.php'
                    . PHP_EOL . 'app/etc/env.php' . PHP_EOL,
            ],
        ];
    }

    /**
     * @expectedExceptionMessage Sorry error
     * @expectedException \Exception
     */
    public function testExecuteWithException()
    {
        $this->loggerMock->expects($this->once())
            ->method('critical')
            ->with('Sorry error');
        $this->backupFilesListMock->expects($this->once())
            ->method('get')
            ->willThrowException(new \Exception('Sorry error'));
        $tester = new CommandTester($this->command);
        $tester->execute([]);

        $this->assertSame(1, $tester->getStatusCode());
    }
}
