<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Unit\Shell;

use Magento\MagentoCloud\App\Logger\Sanitizer;
use Magento\MagentoCloud\Filesystem\SystemList;
use Magento\MagentoCloud\Shell\ProcessFactory;
use Magento\MagentoCloud\Shell\ResultFactory;
use Magento\MagentoCloud\Shell\Shell;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Exception\LogicException;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

/**
 * @inheritdoc
 */
class ShellTest extends TestCase
{
    /**
     * @var Shell
     */
    private $shell;

    /**
     * @var LoggerInterface|MockObject
     */
    private $loggerMock;

    /**
     * @var SystemList|MockObject
     */
    private $systemListMock;

    /**
     * @var Sanitizer|MockObject
     */
    private $sanitizerMock;

    /**
     * @var ProcessFactory|MockObject
     */
    private $processFactoryMock;

    /**
     * @var ResultFactory|MockObject
     */
    private $resultFactoryMock;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        $this->loggerMock = $this->getMockForAbstractClass(LoggerInterface::class);
        $this->systemListMock = $this->createMock(SystemList::class);
        $this->sanitizerMock = $this->createMock(Sanitizer::class);
        $this->processFactoryMock = $this->createMock(ProcessFactory::class);
        $this->resultFactoryMock = $this->createMock(ResultFactory::class);

        $this->shell = new Shell(
            $this->loggerMock,
            $this->systemListMock,
            $this->processFactoryMock,
            $this->resultFactoryMock,
            $this->sanitizerMock
        );
    }

    /**
     * @param string $processOutput
     * @dataProvider executeDataProvider
     */
    public function testExecute($processOutput)
    {
        $command = 'ls';
        $args = ['-al'];
        $magentoRoot = '/magento';

        $processMock = $this->createMock(Process::class);
        $processMock->expects($this->once())
            ->method('getCommandLine')
            ->willReturn('ls -la');
        $processMock->expects($this->once())
            ->method('mustRun');
        $processMock->expects($this->once())
            ->method('getOutput')
            ->willReturn($processOutput);
        $this->processFactoryMock->expects($this->once())
            ->method('create')
            ->with([
                'commandline' => [$command, $args[0]],
                'cwd' => $magentoRoot,
                'timeout' => 0
            ])
            ->willReturn($processMock);
        $this->systemListMock->expects($this->once())
            ->method('getMagentoRoot')
            ->willReturn($magentoRoot);
        $this->loggerMock->expects($this->once())
            ->method('info')
            ->with('ls -la');
        $this->sanitizerMock->expects($this->never())
            ->method('sanitize');

        if ($processOutput) {
            $this->loggerMock->expects($this->once())
                ->method('debug')
                ->with($processOutput);
        }

        $this->shell->execute($command, $args);
    }

    /**
     * @return array
     */
    public function executeDataProvider(): array
    {
        return [
            'empty process output' => ['processOutput' => ''],
            'non empty process output' => ['processOutput' => 'test'],
        ];
    }

    public function testExecuteHandleOutputException()
    {
        $command = 'ls';
        $magentoRoot = '/magento';

        $processMock = $this->createMock(Process::class);
        $processMock->expects($this->once())
            ->method('getCommandLine')
            ->willReturn('ls -la');
        $processMock->expects($this->once())
            ->method('mustRun');
        $processMock->expects($this->once())
            ->method('getOutput')
            ->willThrowException(new LogicException('something went wrong'));
        $this->processFactoryMock->expects($this->once())
            ->method('create')
            ->with([
                'commandline' => $command,
                'cwd' => $magentoRoot,
                'timeout' => 0
            ])
            ->willReturn($processMock);
        $this->systemListMock->expects($this->once())
            ->method('getMagentoRoot')
            ->willReturn($magentoRoot);
        $this->loggerMock->expects($this->once())
            ->method('error')
            ->with('Can\'t get command output: something went wrong');

        $this->shell->execute($command);
    }

    /**
     * @expectedException \Magento\MagentoCloud\Shell\ShellException
     * @expectedExceptionMessage Command ls -al --password="***" failed
     * @expectedExceptionCode 3
     */
    public function testExecuteException()
    {
        $command = 'ls -al --password="123"';
        $magentoRoot = '/magento';

        /** @var Process|MockObject $processMock */
        $processMock = $this->createMock(Process::class);
        $processMock->expects($this->exactly(2))
            ->method('getCommandLine')
            ->willReturn($command);
        $processMock->expects($this->exactly(2))
            ->method('getExitCode')
            ->willReturn(3);
        $processMock->expects($this->once())
            ->method('mustRun')
            ->willThrowException(new ProcessFailedException($processMock));
        $processMock->expects($this->never())
            ->method('getOutput');
        $this->processFactoryMock->expects($this->once())
            ->method('create')
            ->with([
                'commandline' => $command,
                'cwd' => $magentoRoot,
                'timeout' => 0
            ])
            ->willReturn($processMock);
        $this->systemListMock->expects($this->once())
            ->method('getMagentoRoot')
            ->willReturn($magentoRoot);
        $this->sanitizerMock->expects($this->once())
            ->method('sanitize')
            ->with($this->stringContains('ls -al --password="123"'))
            ->willReturn('Command ls -al --password="***" failed');
        $this->loggerMock->expects($this->once())
            ->method('info')
            ->with($command);
        $this->loggerMock->expects($this->never())
            ->method('debug');

        $this->shell->execute($command);
    }

    public function testExecuteWithArguments()
    {
        $command = 'ls -al';
        $magentoRoot = '/magento';
        $arguments = ['arg1', 'arg2'];

        /** @var Process|MockObject $processMock */
        $processMock = $this->createMock(Process::class);
        $processMock->expects($this->once())
            ->method('getCommandLine')
            ->willReturn('ls -al arg1 arg2');
        $this->processFactoryMock->expects($this->once())
            ->method('create')
            ->with([
                'commandline' => array_merge([$command], $arguments),
                'cwd' => $magentoRoot,
                'timeout' => 0
            ])
            ->willReturn($processMock);
        $this->systemListMock->expects($this->once())
            ->method('getMagentoRoot')
            ->willReturn($magentoRoot);
        $this->loggerMock->expects($this->once())
            ->method('info')
            ->with('ls -al arg1 arg2');

        $this->shell->execute($command, ['arg1', 'arg2']);
    }
}
