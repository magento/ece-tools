<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Test\Unit\Command;

use Magento\MagentoCloud\App\ErrorInfo;
use Magento\MagentoCloud\App\Logger\Error\ReaderInterface;
use Magento\MagentoCloud\Command\ErrorShow;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * @inheritDoc
 */
class ErrorShowTest extends TestCase
{
    /**
     * @var ErrorShow
     */
    private $command;

    /**
     * @var ErrorInfo|MockObject
     */
    private $errorInfoMock;

    /**
     * @var ReaderInterface|MockObject
     */
    private $readerMock;

    protected function setUp(): void
    {
        $this->errorInfoMock = $this->createMock(ErrorInfo::class);
        $this->readerMock = $this->getMockForAbstractClass(ReaderInterface::class);

        $this->command = new ErrorShow($this->errorInfoMock, $this->readerMock);
    }

    public function testExecuteWithCode()
    {
        $this->errorInfoMock->expects($this->once())
            ->method('get')
            ->with(12)
            ->willReturn([
                'title' => 'some error',
                'suggestion' => 'some suggestion',
                'stage' => 'deploy'
            ]);
        $this->readerMock->expects($this->never())
            ->method('read');

        $tester = new CommandTester($this->command);
        $tester->execute([ErrorShow::ARGUMENT_ERROR_CODE => '12']);

        $message = implode(PHP_EOL, [
            'errorCode: 12',
            'stage: deploy',
            'suggestion: some suggestion',
            'title: some error'
        ]);
        $this->assertStringContainsString($message, $tester->getDisplay());
    }

    public function testExecuteWithWrongErrorCode()
    {
        $this->errorInfoMock->expects($this->once())
            ->method('get')
            ->with(1111)
            ->willReturn([]);
        $this->readerMock->expects($this->never())
            ->method('read');

        $tester = new CommandTester($this->command);
        $tester->execute([ErrorShow::ARGUMENT_ERROR_CODE => '1111']);

        $this->assertStringContainsString(
            'Error with code 1111 is not registered in the error schema',
            $tester->getDisplay()
        );
    }

    public function testExecuteWithoutCode()
    {
        $this->errorInfoMock->expects($this->never())
            ->method('get');
        $this->readerMock->expects($this->once())
            ->method('read')
            ->willReturn([
                [
                    'errorCode: 12',
                    'stage' => 'deploy',
                    'suggestion' => 'some suggestion',
                    'title' => 'some warning error',
                    'type' => 'warning'
                ],
                [
                    'errorCode' => '13',
                    'stage' => 'deploy',
                    'suggestion' => 'some suggestion',
                    'title' => 'some critical error',
                    'type' => 'critical'
                ],
            ]);

        $tester = new CommandTester($this->command);
        $tester->execute([]);

        $this->assertStringContainsString('errorCode: 13', $tester->getDisplay());
        $this->assertStringContainsString('errorCode: 12', $tester->getDisplay());
    }

    public function testExecuteWithoutCodeJsonFormat()
    {
        $errors = [
            [
                'errorCode: 12',
                'stage' => 'deploy',
                'suggestion' => 'some suggestion',
                'title' => 'some warning error',
                'type' => 'warning'
            ],
            [
                'errorCode' => '13',
                'stage' => 'deploy',
                'suggestion' => 'some suggestion',
                'title' => 'some critical error',
                'type' => 'critical'
            ],
        ];
        $this->errorInfoMock->expects($this->never())
            ->method('get');
        $this->readerMock->expects($this->once())
            ->method('read')
            ->willReturn($errors);

        $tester = new CommandTester($this->command);
        $tester->execute(['--json' => true]);

        $this->assertStringContainsString(json_encode($errors), $tester->getDisplay());
    }

    public function testExecuteWithoutCodeEmptyLog()
    {
        $this->errorInfoMock->expects($this->never())
            ->method('get');
        $this->readerMock->expects($this->once())
            ->method('read')
            ->willReturn([]);

        $tester = new CommandTester($this->command);
        $tester->execute([]);

        $this->assertStringContainsString('The error log is empty or does not exist', $tester->getDisplay());
    }
}
