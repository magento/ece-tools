<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Test\Unit\Shell;

use Magento\MagentoCloud\App\Logger\Sanitizer;
use Magento\MagentoCloud\Filesystem\SystemList;
use Magento\MagentoCloud\Shell\ProcessException;
use Magento\MagentoCloud\Shell\ProcessFactory;
use Magento\MagentoCloud\Shell\ProcessInterface;
use Magento\MagentoCloud\Shell\Shell;
use Magento\MagentoCloud\Shell\ShellException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Exception\LogicException;

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
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->loggerMock = $this->getMockForAbstractClass(LoggerInterface::class);
        $this->systemListMock = $this->createMock(SystemList::class);
        $this->sanitizerMock = $this->createMock(Sanitizer::class);
        $this->processFactoryMock = $this->createMock(ProcessFactory::class);

        $this->shell = new Shell(
            $this->loggerMock,
            $this->systemListMock,
            $this->processFactoryMock,
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
        $args = ['-al', '0'];
        $magentoRoot = '/magento';
        $commandWithArgs = "ls '-al' '0'";

        $processMock = $this->getMockForAbstractClass(ProcessInterface::class);
        $processMock->expects($this->once())
            ->method('getCommandLine')
            ->willReturn($commandWithArgs);
        $processMock->expects($this->once())
            ->method('execute');
        $processMock->expects($this->once())
            ->method('getOutput')
            ->willReturn($processOutput);
        $this->processFactoryMock->expects($this->once())
            ->method('create')
            ->with([
                'command' => $commandWithArgs,
                'cwd' => $magentoRoot,
                'timeout' => null
            ])
            ->willReturn($processMock);
        $this->systemListMock->expects($this->once())
            ->method('getMagentoRoot')
            ->willReturn($magentoRoot);

        $logExpects = [[$commandWithArgs]];
        if ($processOutput) {
            $logExpects[] = [$processOutput];
        }
        $this->loggerMock->expects($this->exactly(count($logExpects)))
            ->method('debug')
            ->withConsecutive(...$logExpects);
        $this->sanitizerMock->expects($this->never())
            ->method('sanitize');

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

        $processMock = $this->createMock(ProcessInterface::class);
        $processMock->expects($this->once())
            ->method('getCommandLine')
            ->willReturn('ls -la');
        $processMock->expects($this->once())
            ->method('execute');
        $processMock->expects($this->once())
            ->method('getOutput')
            ->willThrowException(new LogicException('something went wrong'));
        $this->processFactoryMock->expects($this->once())
            ->method('create')
            ->with([
                'command' => $command,
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

    public function testExecuteException()
    {
        $this->expectException(ShellException::class);
        $this->expectExceptionMessage('Command ls -al --password="***" failed');
        $this->expectExceptionCode(3);

        $command = 'ls -al --password="123"';
        $magentoRoot = '/magento';

        /** @var ProcessInterface|MockObject $processMock */
        $processMock = $this->createMock(ProcessInterface::class);
        $processMock->expects($this->once())
            ->method('getCommandLine')
            ->willReturn($command);
        $processMock->expects($this->once())
            ->method('execute')
            ->willThrowException(new ProcessException(sprintf('Command %s failed', $command), 3));
        $processMock->expects($this->never())
            ->method('getOutput');
        $this->processFactoryMock->expects($this->once())
            ->method('create')
            ->with([
                'command' => $command,
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
            ->method('debug')
            ->with($command);

        $this->shell->execute($command);
    }

    public function testExecuteWithArguments()
    {
        $command = 'ls -al';
        $magentoRoot = '/magento';

        /** @var ProcessInterface|MockObject $processMock */
        $processMock = $this->createMock(ProcessInterface::class);
        $processMock->expects($this->once())
            ->method('getCommandLine')
            ->willReturn("ls -al 'arg1' 'arg2'");
        $this->processFactoryMock->expects($this->once())
            ->method('create')
            ->with([
                'command' => "ls -al 'arg1' 'arg2'",
                'cwd' => $magentoRoot,
                'timeout' => 0
            ])
            ->willReturn($processMock);
        $this->systemListMock->expects($this->once())
            ->method('getMagentoRoot')
            ->willReturn($magentoRoot);
        $this->loggerMock->expects($this->once())
            ->method('debug')
            ->with("ls -al 'arg1' 'arg2'");

        $this->shell->execute($command, ['arg1', 'arg2']);
    }
}
