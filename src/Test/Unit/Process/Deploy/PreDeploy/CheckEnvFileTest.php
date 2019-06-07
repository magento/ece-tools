<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Test\Unit\Process\Deploy\PreDeploy;

use Magento\MagentoCloud\App\GenericException;
use Magento\MagentoCloud\Config\Deploy\Reader;
use Magento\MagentoCloud\Config\Deploy\Writer;
use Magento\MagentoCloud\Config\State;
use Magento\MagentoCloud\Config\Validator\Deploy\DatabaseConfiguration;
use Magento\MagentoCloud\Config\Validator\Result\Error;
use Magento\MagentoCloud\Filesystem\BackupList;
use Magento\MagentoCloud\Filesystem\Driver\File;
use Magento\MagentoCloud\Filesystem\FileList;
use Magento\MagentoCloud\Process\Deploy\PreDeploy\CheckEnvFile;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * @inheritdoc
 */
class CheckEnvFileTest extends TestCase
{
    /**
     * @var CheckEnvFile
     */
    private $process;

    /**
     * @var LoggerInterface|MockObject
     */
    private $loggerMock;

    /**
     * @var FileList|MockObject
     */
    private $fileListMock;

    /**
     * @var File|MockObject
     */
    private $fileMock;

    /**
     * @var Reader|MockObject
     */
    private $readerMock;

    /**
     * @var Writer|MockObject
     */
    private $writerMock;

    /**
     * @var State|MockObject
     */
    private $stateMock;

    /**
     * @var DatabaseConfiguration|MockObject
     */
    private $databaseValidatorMock;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        $this->loggerMock = $this->getMockForAbstractClass(LoggerInterface::class);
        $this->fileListMock = $this->createMock(FileList::class);
        $this->fileMock = $this->createMock(File::class);
        $this->readerMock = $this->createMock(Reader::class);
        $this->writerMock = $this->createMock(Writer::class);
        $this->stateMock = $this->createMock(State::class);
        $this->databaseValidatorMock = $this->createMock(DatabaseConfiguration::class);

        $this->process = new CheckEnvFile(
            $this->loggerMock,
            $this->fileListMock,
            $this->fileMock,
            $this->readerMock,
            $this->writerMock,
            $this->stateMock,
            $this->databaseValidatorMock
        );
    }

    public function testExecuteWrongDatabaseConfig()
    {
        $this->databaseValidatorMock->expects($this->once())
            ->method('validate')
            ->willReturn(new Error('some error'));
        $this->fileListMock->expects($this->never())
            ->method('getEnv');

        $this->process->execute();
    }

    public function testExecuteNotInstalled()
    {
        $this->stateMock->expects($this->once())
            ->method('isInstalled')
            ->willReturn(false);
        $this->fileListMock->expects($this->never())
            ->method('getEnv');

        $this->process->execute();
    }

    public function testExecuteEnvExistsWithInstallDate()
    {
        $envPath = '/path/to/env.php';

        $this->stateMock->expects($this->once())
            ->method('isInstalled')
            ->willReturn(true);
        $this->fileListMock->expects($this->once())
            ->method('getEnv')
            ->willReturn($envPath);
        $this->fileMock->expects($this->once())
            ->method('isExists')
            ->with($envPath)
            ->willReturn(true);
        $this->readerMock->expects($this->once())
            ->method('read')
            ->willReturn(['install' => ['date' => '01-01-2000']]);
        $this->loggerMock->expects($this->once())
            ->method('info')
            ->with('Magento was installed on 01-01-2000');
        $this->loggerMock->expects($this->never())
            ->method('warning');

        $this->process->execute();
    }

    public function testExecuteEnvExistsWithoutInstallDate()
    {
        $envPath = '/path/to/env.php';

        $this->stateMock->expects($this->once())
            ->method('isInstalled')
            ->willReturn(true);
        $this->fileListMock->expects($this->once())
            ->method('getEnv')
            ->willReturn($envPath);
        $this->fileMock->expects($this->once())
            ->method('isExists')
            ->with($envPath)
            ->willReturn(true);
        $this->readerMock->expects($this->once())
            ->method('read')
            ->willReturn(['config' => ['value']]);
        $this->writerMock->expects($this->once())
            ->method('update');
        $this->loggerMock->expects($this->never())
            ->method('info');
        $this->loggerMock->expects($this->never())
            ->method('warning');

        $this->process->execute();
    }

    public function testExecuteEnvNotExistsRestoreFromBackup()
    {
        $envPath = '/path/to/env.php';
        $envPathBack = '/path/to/env.php' . BackupList::BACKUP_SUFFIX;

        $this->stateMock->expects($this->once())
            ->method('isInstalled')
            ->willReturn(true);
        $this->fileListMock->expects($this->once())
            ->method('getEnv')
            ->willReturn($envPath);
        $this->fileMock->expects($this->exactly(2))
            ->method('isExists')
            ->withConsecutive(
                [$envPath],
                [$envPathBack]
            )
            ->willReturnOnConsecutiveCalls(false, true);
        $this->loggerMock->expects($this->once())
            ->method('warning')
            ->with('Magento is installed but the environment configuration file doesn\'t exist.');
        $this->loggerMock->expects($this->once())
            ->method('info')
            ->with('Restoring environment configuration file from the backup.');
        $this->fileMock->expects($this->once())
            ->method('copy')
            ->with($envPathBack, $envPath);

        $this->process->execute();
    }

    public function testExecuteEnvAndBackupNotExists()
    {
        $envPath = '/path/to/env.php';
        $envPathBack = '/path/to/env.php' . BackupList::BACKUP_SUFFIX;

        $this->stateMock->expects($this->once())
            ->method('isInstalled')
            ->willReturn(true);
        $this->fileListMock->expects($this->once())
            ->method('getEnv')
            ->willReturn($envPath);
        $this->fileMock->expects($this->exactly(2))
            ->method('isExists')
            ->withConsecutive(
                [$envPath],
                [$envPathBack]
            )
            ->willReturnOnConsecutiveCalls(false, false);
        $this->loggerMock->expects($this->once())
            ->method('warning')
            ->with('Magento is installed but the environment configuration file doesn\'t exist.');
        $this->loggerMock->expects($this->once())
            ->method('info')
            ->with('Generating new environment configuration file.');
        $this->writerMock->expects($this->once())
            ->method('update');
        $this->fileMock->expects($this->never())
            ->method('copy');

        $this->process->execute();
    }

    /**
     * @expectedException \Magento\MagentoCloud\Process\ProcessException
     * @expectedExceptionMessage some error message
     */
    public function testExecuteWithException()
    {
        $this->stateMock->expects($this->once())
            ->method('isInstalled')
            ->willThrowException(new GenericException('some error message'));
        $this->fileListMock->expects($this->never())
            ->method('getEnv');

        $this->process->execute();
    }
}
