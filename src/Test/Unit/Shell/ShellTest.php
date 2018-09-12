<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Unit\Shell;

use Magento\MagentoCloud\Filesystem\SystemList;
use PHPUnit\Framework\MockObject\Matcher\InvokedCount;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Magento\MagentoCloud\Shell\Shell;
use Psr\Log\LoggerInterface;
use Monolog\Logger;

/**
 * @inheritdoc
 */
class ShellTest extends TestCase
{
    use \phpmock\phpunit\PHPMock;

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
     * @inheritdoc
     */
    protected function setUp()
    {
        $this->loggerMock = $this->getMockForAbstractClass(LoggerInterface::class);
        $this->systemListMock = $this->createMock(SystemList::class);

        $this->shell = new Shell($this->loggerMock, $this->systemListMock);
    }

    /**
     * @param string $execOutput
     * @dataProvider executeDataProvider
     */
    public function testExecute($execOutput)
    {
        $command = 'ls -al';
        $magentoRoot = '/magento';
        $execCommand = 'cd ' . $magentoRoot . ' && ' . $command . ' 2>&1';

        $execMock = $this->getFunctionMock('Magento\MagentoCloud\Shell', 'exec');
        $execMock->expects($this->once())
            ->willReturnCallback(function ($cmd, &$output, &$status) use ($execCommand, $execOutput) {
                $this->assertSame($execCommand, $cmd);
                $status = 0;
                $output = $execOutput;
            });

        $this->systemListMock->expects($this->once())
            ->method('getMagentoRoot')
            ->willReturn($magentoRoot);

        $this->loggerMock->expects($this->once())
            ->method('info')
            ->with($command);
        if ($execOutput) {
            $this->loggerMock->expects($this->once())
                ->method('log')
                ->with(Logger::DEBUG, PHP_EOL . '  ' . $execOutput[0]);
        }

        $this->shell->execute($command);
    }

    /**
     * @return array
     */
    public function executeDataProvider(): array
    {
        return [
            ['execOutput' => []],
            ['execOutput' => ['test']],
        ];
    }

    /**
     * @param InvokedCount $logExpects
     * @param array $execOutput
     * @expectedException \RuntimeException
     * @expectedExceptionMessage Command ls -al returned code 123
     * @expectedExceptionCode 123
     * @dataProvider executeExceptionDataProvider
     */
    public function testExecuteException($logExpects, array $execOutput)
    {
        $command = 'ls -al';
        $magentoRoot = '/magento';
        $execCommand = 'cd ' . $magentoRoot . ' && ' . $command . ' 2>&1';

        $execMock = $this->getFunctionMock('Magento\MagentoCloud\Shell', 'exec');
        $execMock->expects($this->once())
            ->willReturnCallback(function ($cmd, &$output, &$status) use ($execCommand, $execOutput) {
                $this->assertSame($execCommand, $cmd);
                $status = 123;
                $output = $execOutput;
            });

        $this->systemListMock->expects($this->once())
            ->method('getMagentoRoot')
            ->willReturn($magentoRoot);

        $this->loggerMock->expects($this->once())
            ->method('info')
            ->with($command);
        $this->loggerMock->expects($logExpects)
            ->method('log')
            ->with(Logger::CRITICAL, PHP_EOL . '  test');

        $this->shell->execute($command);
    }

    /**
     * @return array
     */
    public function executeExceptionDataProvider(): array
    {
        return [
            [
                'logExpects' => $this->never(),
                'execOutput' => [],
            ],
            [
                'logExpects' => $this->once(),
                'execOutput' => ['test'],
            ],
        ];
    }

    public function testExecuteWithArguments()
    {
        $command = 'ls -al';
        $magentoRoot = '/magento';
        $execCommand = 'cd ' . $magentoRoot . ' && ' . $command . ' \'arg1\' \'arg2\' 2>&1';

        $execMock = $this->getFunctionMock('Magento\MagentoCloud\Shell', 'exec');
        $execMock->expects($this->once())
            ->willReturnCallback(function ($cmd, &$output, &$status) use ($execCommand) {
                $this->assertSame($execCommand, $cmd);
                $status = 0;
                $output = [];
            });

        $this->systemListMock->expects($this->once())
            ->method('getMagentoRoot')
            ->willReturn($magentoRoot);
        $this->loggerMock->expects($this->once())
            ->method('info')
            ->with($command . ' \'arg1\' \'arg2\'');

        $this->shell->execute($command, ['arg1', 'arg2']);
    }

    public function testExecuteWithStringArgument()
    {
        $command = 'ls -al';
        $magentoRoot = '/magento';
        $execCommand = 'cd ' . $magentoRoot . ' && ' . $command . ' \'arg1\' 2>&1';

        $execMock = $this->getFunctionMock('Magento\MagentoCloud\Shell', 'exec');
        $execMock->expects($this->once())
            ->willReturnCallback(function ($cmd, &$output, &$status) use ($execCommand) {
                $this->assertSame($execCommand, $cmd);
                $status = 0;
                $output = [];
            });

        $this->systemListMock->expects($this->once())
            ->method('getMagentoRoot')
            ->willReturn($magentoRoot);
        $this->loggerMock->expects($this->once())
            ->method('info')
            ->with($command . ' \'arg1\'');

        $this->shell->execute($command, 'arg1');
    }
}
