<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Test\Unit\App\Logger\Formatter;

use Magento\MagentoCloud\App\ErrorInfo;
use Magento\MagentoCloud\App\Logger\Error\ReaderInterface;
use Magento\MagentoCloud\App\Logger\Formatter\JsonErrorFormatter;
use Magento\MagentoCloud\Filesystem\FileSystemException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class JsonErrorFormatterTest extends TestCase
{
    /**
     * @var JsonErrorFormatter
     */
    private $jsonErrorFormatter;

    /**
     * @var ErrorInfo|MockObject
     */
    private $errorInfoMock;

    /**
     * @var ReaderInterface|MockObject
     */
    private $readerMock;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        $this->errorInfoMock = $this->createMock(ErrorInfo::class);
        $this->readerMock = $this->getMockForAbstractClass(ReaderInterface::class);

        $this->jsonErrorFormatter = new JsonErrorFormatter(
            $this->errorInfoMock,
            $this->readerMock
        );
    }

    public function testFormat(): void
    {
        $this->readerMock->expects($this->once())
            ->method('read')
            ->willReturn([]);
        $this->errorInfoMock->expects($this->once())
            ->method('get')
            ->with(11)
            ->willReturn([
                'title' => 'some custom title',
                'type' => 'warning'
            ]);

        if (\Monolog\Logger::API == 3) {
            $logRecord = new \Monolog\LogRecord(
                datetime: new \DateTimeImmutable(),
                channel: 'testChannel',
                level: \Monolog\Level::Warning,
                message: 'some error',
                context: ['errorCode' => 11, ]
            );
        } else {
            $logRecord = ['message' => 'some error', 'context' => ['errorCode' => 11]];
        }

        $this->assertEquals(
            '{"errorCode":11,"title":"some error","type":"warning"}' . PHP_EOL,
            $this->jsonErrorFormatter->format($logRecord)
        );
    }

    public function testFormatEmptyError(): void
    {
        $this->readerMock->expects($this->once())
            ->method('read')
            ->willReturn([]);
        $this->errorInfoMock->expects($this->once())
            ->method('get')
            ->with(11)
            ->willReturn([]);

        if (\Monolog\Logger::API == 3) {
            $logRecord = new \Monolog\LogRecord(
                datetime: new \DateTimeImmutable(),
                channel: 'testChannel',
                level: \Monolog\Level::Warning,
                message: 'some error',
                context: ['errorCode' => 11, 'suggestion' => 'some suggestion']
            );
        } else {
            $logRecord = [
                'message' => 'some error',
                'context' => ['errorCode' => 11, 'suggestion' => 'some suggestion']
            ];
        }

        $this->assertEquals(
            '{"errorCode":11,"suggestion":"some suggestion","title":"some error"}' . PHP_EOL,
            $this->jsonErrorFormatter->format($logRecord)
        );
    }

    public function testFormatMessageAlreadyLogged(): void
    {
        $this->readerMock->expects($this->once())
            ->method('read')
            ->willReturn([
                11 => ['message' => 'some error']
            ]);
        $this->errorInfoMock->expects($this->never())
            ->method('get');

        if (\Monolog\Logger::API == 3) {
            $logRecord = new \Monolog\LogRecord(
                datetime: new \DateTimeImmutable(),
                channel: 'testChannel',
                level: \Monolog\Level::Warning,
                message: 'some error',
                context: ['errorCode' => 11]
            );
        } else {
            $logRecord = [
                'message' => 'some error',
                'context' => ['errorCode' => 11]
            ];
        }
        $this->assertEmpty(
            $this->jsonErrorFormatter->format($logRecord)
        );
    }

    public function testFormatNoErrorCode(): void
    {
        $this->readerMock->expects($this->never())
            ->method('read');
        $this->errorInfoMock->expects($this->never())
            ->method('get');

        if (\Monolog\Logger::API == 3) {
            $logRecord = new \Monolog\LogRecord(
                datetime: new \DateTimeImmutable(),
                channel: 'testChannel',
                level: \Monolog\Level::Warning,
                message: 'test',
                context: []
            );
        } else {
            $logRecord = [
                'message' => 'test',
                'context' => []
            ];
        }
        $this->assertEmpty($this->jsonErrorFormatter->format($logRecord));
    }

    public function testFormatWithException(): void
    {
        $this->readerMock->expects($this->any())
            ->method('read')
            ->willThrowException(new FileSystemException('error'));
            
        if (\Monolog\Logger::API == 3) {
            $logRecord = new \Monolog\LogRecord(
                datetime: new \DateTimeImmutable(),
                channel: 'testChannel',
                level: \Monolog\Level::Warning,
                message: 'test',
                context: []
            );
        } else {
            $logRecord = [
                'message' => 'test',
                'context' => ['errorCode' => 11]
            ];
        }
        $this->assertEmpty($this->jsonErrorFormatter->format($logRecord));
    }
}
