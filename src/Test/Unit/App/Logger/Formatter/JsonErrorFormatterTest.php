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

/**
 * @inheritDoc
 */
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
    protected function setUp()
    {
        $this->errorInfoMock = $this->createMock(ErrorInfo::class);
        $this->readerMock = $this->getMockForAbstractClass(ReaderInterface::class);

        $this->jsonErrorFormatter = new JsonErrorFormatter(
            $this->errorInfoMock,
            $this->readerMock
        );
    }

    public function testFormat()
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

        $this->assertEquals(
            '{"errorCode":11,"title":"some error","type":"warning"}' . PHP_EOL,
            $this->jsonErrorFormatter->format(['message' => 'some error', 'context' => ['errorCode' => 11]])
        );
    }

    public function testFormatEmptyError()
    {
        $this->readerMock->expects($this->once())
            ->method('read')
            ->willReturn([]);
        $this->errorInfoMock->expects($this->once())
            ->method('get')
            ->with(11)
            ->willReturn([]);

        $this->assertEquals(
            '{"errorCode":11,"suggestion":"some suggestion","title":"some error"}' . PHP_EOL,
            $this->jsonErrorFormatter->format([
                'message' => 'some error',
                'context' => ['errorCode' => 11, 'suggestion' => 'some suggestion']
            ])
        );
    }

    public function testFormatMessageAlreadyLogged()
    {
        $this->readerMock->expects($this->once())
            ->method('read')
            ->willReturn([
                11 => ['message' => 'some error']
            ]);
        $this->errorInfoMock->expects($this->never())
            ->method('get');

        $this->assertFalse(
            $this->jsonErrorFormatter->format(['message' => 'some error', 'context' => ['errorCode' => 11]])
        );
    }

    public function testFormatNoErrorCode()
    {
        $this->readerMock->expects($this->never())
            ->method('read');
        $this->errorInfoMock->expects($this->never())
            ->method('get');

        $this->assertFalse($this->jsonErrorFormatter->format(['message' => 'test']));
    }

    public function testFormatWithException()
    {
        $this->readerMock->expects($this->once())
            ->method('read')
            ->willThrowException(new FileSystemException('error'));

        $this->assertFalse($this->jsonErrorFormatter->format(['message' => 'test', 'context' => ['errorCode' => 11]]));
    }
}
