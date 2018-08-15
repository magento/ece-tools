<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Unit\Process\Deploy\InstallUpdate\Update;

use Magento\MagentoCloud\Config\Environment;
use Magento\MagentoCloud\Config\Stage\DeployInterface;
use Magento\MagentoCloud\Filesystem\DirectoryList;
use Magento\MagentoCloud\Filesystem\Flag\Manager as FlagManager;
use Magento\MagentoCloud\Process\Deploy\InstallUpdate\Update\Setup;
use Magento\MagentoCloud\Shell\ExecBinMagento;
use Magento\MagentoCloud\Shell\ShellException;
use Magento\MagentoCloud\Filesystem\FileList;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * @inheritdoc
 */
class SetupTest extends TestCase
{
    /**
     * @var Setup
     */
    private $process;

    /**
     * @var Environment|MockObject
     */
    private $environmentMock;

    /**
     * @var LoggerInterface|MockObject
     */
    private $loggerMock;

    /**
     * @var ExecBinMagento|MockObject
     */
    private $shellMock;

    /**
     * @var FlagManager|MockObject
     */
    private $flagManagerMock;

    /**
     * @var FileList|MockObject
     */
    private $fileListMock;

    /**
     * @var DirectoryList|MockObject
     */
    private $directoryListMock;

    /**
     * @var DeployInterface|MockObject
     */
    private $stageConfigMock;

    /**
     * @var string
     */
    private $logPath = ECE_BP . '/tests/unit/tmp/update.log';

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        $this->environmentMock = $this->createMock(Environment::class);
        $this->shellMock = $this->createMock(ExecBinMagento::class);
        $this->loggerMock = $this->getMockForAbstractClass(LoggerInterface::class);
        $this->directoryListMock = $this->createMock(DirectoryList::class);
        $this->fileListMock = $this->createMock(FileList::class);
        $this->flagManagerMock = $this->createMock(FlagManager::class);
        $this->stageConfigMock = $this->getMockForAbstractClass(DeployInterface::class);

        $this->process = new Setup(
            $this->loggerMock,
            $this->environmentMock,
            $this->shellMock,
            $this->directoryListMock,
            $this->fileListMock,
            $this->flagManagerMock,
            $this->stageConfigMock
        );

        // Initialize log file
        if (!is_dir(dirname($this->logPath))) {
            mkdir(dirname($this->logPath), 0777, true);
        }
        file_put_contents($this->logPath, 'Previous log' . PHP_EOL);
    }

    public function testExecute()
    {
        $this->directoryListMock->method('getMagentoRoot')
            ->willReturn('magento_root');
        $this->fileListMock->expects($this->once())
            ->method('getInstallUpgradeLog')
            ->willReturn($this->logPath);
        $this->stageConfigMock->expects($this->once())
            ->method('get')
            ->with(DeployInterface::VAR_VERBOSE_COMMANDS)
            ->willReturn('-v');
        $this->flagManagerMock->expects($this->exactly(2))
            ->method('delete')
            ->with(FlagManager::FLAG_REGENERATE);
        $this->shellMock->expects($this->exactly(3))
            ->method('execute')
            ->withConsecutive(
                ['maintenance:enable', '-v'],
                ['setup:upgrade', ['--keep-generated', '-v']],
                ['maintenance:disable', '-v']
            )
            ->willReturnOnConsecutiveCalls(
                [],
                ['Doing upgrade', 'Upgrade complete'],
                []
            );
        $this->loggerMock->expects($this->once())
            ->method('info')
            ->with('Running setup upgrade.');
        $this->loggerMock->expects($this->exactly(2))
            ->method('notice')
            ->withConsecutive(
                ['Enabling Maintenance mode.'],
                ['Maintenance mode is disabled.']
            );

        $this->process->execute();

        $this->assertFileIsReadable($this->logPath);
        $this->assertSame("Previous log\nDoing upgrade\nUpgrade complete\n", file_get_contents($this->logPath));
    }

    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessage Error during command execution
     */
    public function testExecuteWithException()
    {
        $this->flagManagerMock->expects($this->once())
            ->method('delete')
            ->with(FlagManager::FLAG_REGENERATE);
        $this->fileListMock->expects($this->once())
            ->method('getInstallUpgradeLog')
            ->willReturn($this->logPath);
        $this->shellMock->method('execute')
            ->willThrowException(new ShellException('Error during command execution', 1, ['Output from command']));

        $this->process->execute();

        $this->assertFileIsReadable($this->logPath);
        $this->assertSame("Previous log\nOutput from command\n", file_get_contents($this->logPath));
    }

    /**
     * @expectedException \Exception
     * @expectedExceptionMessage Something else has gone wrong
     */
    public function testExecuteOtherException()
    {
        $this->flagManagerMock->expects($this->once())
            ->method('delete')
            ->with(FlagManager::FLAG_REGENERATE);
        $this->fileListMock->expects($this->once())
            ->method('getInstallUpgradeLog')
            ->willReturn($this->logPath);
        $this->shellMock->method('execute')
            ->willThrowException(new \Exception('Something else has gone wrong'));

        $this->process->execute();

        $this->assertFileIsReadable($this->logPath);
        $this->assertSame("Previous log\nSomething else has gone wrong\n", file_get_contents($this->logPath));
    }
}
