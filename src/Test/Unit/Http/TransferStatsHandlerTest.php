<?php

/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\MagentoCloud\Test\Unit\Http;

use GuzzleHttp\TransferStats;
use Magento\MagentoCloud\Filesystem\Driver\File;
use Magento\MagentoCloud\Filesystem\FileList;
use Magento\MagentoCloud\Http\TransferStatsHandler;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\UriInterface;
use Psr\Log\LoggerInterface;

/**
 * {@inheritdoc}
 */
#[AllowMockObjectsWithoutExpectations]
class TransferStatsHandlerTest extends TestCase
{
    /**
     * @var FileList|MockObject
     */
    private $fileListMock;

    /**
     * @var File|MockObject
     */
    private $fileMock;

    /**
     * @var LoggerInterface|MockObject
     */
    private $loggerMock;

    /**
     * @var TransferStatsHandler
     */
    private $handler;

    /**
     * @inheritdoc
     */
    public function setUp(): void
    {
        $this->fileMock = $this->createMock(File::class);
        $this->fileListMock = $this->createMock(FileList::class);
        $this->loggerMock = $this->createMock(LoggerInterface::class);

        $this->handler = new TransferStatsHandler(
            $this->fileMock,
            $this->fileListMock,
            $this->loggerMock
        );
    }

    /**
     * Test stat handler redirect.
     *
     * @return void
     */
    public function testStatHandlerRedirect()
    {
        $mockUriInterface = $this->createMock(UriInterface::class);
        $mockRequest = $this->createMock(RequestInterface::class);
        $mockRequest->method('getUri')
            ->willReturn($mockUriInterface);
        $mockResponse = $this->createMock(ResponseInterface::class);

        $stats = new TransferStats($mockRequest, $mockResponse);

        $mockResponse->expects($this->once())
            ->method('getStatusCode')
            ->willReturn(302);
        $this->loggerMock->expects($this->once())
            ->method('debug')
            ->with('TTFB response was a redirect');

        call_user_func($this->handler, $stats);
    }

    /**
     * Test stat handler transfer time.
     *
     * @return void
     */
    public function testStatHandlerTransferTime(): void
    {
        $mockUriInterface = $this->createMock(UriInterface::class);
        $mockUriInterface->expects($this->any())
            ->method('__toString')
            ->willReturn('/');
        $mockRequest = $this->createMock(RequestInterface::class);
        $mockRequest->expects($this->any())
            ->method('getUri')
            ->willReturn($mockUriInterface);

        $stats = new TransferStats($mockRequest, null, 3.1415926);
        $this->loggerMock->expects($this->once())
            ->method('debug')
            ->with('cURL stats are missing from the request; using total transfer time');
        $this->loggerMock->expects($this->once())
            ->method('info')
            ->with('TTFB test result: 3.142s', ['url' => '/', 'status' => 'unknown']);
        $this->fileListMock->method('getTtfbLog')
            ->willReturn('/path/to/ttfb.json');
        $this->fileMock->expects($this->once())
            ->method('isExists')
            ->with('/path/to/ttfb.json')
            ->willReturn(false);
        $this->fileMock->expects($this->never())
            ->method('fileGetContents');
        $this->fileMock->expects($this->once())
            ->method('filePutContents')
            ->with(
                $this->equalTo('/path/to/ttfb.json'),
                $this->callBack(function (string $subject) {
                    $regMethod = method_exists($this, 'assertMatchesRegularExpression')
                        ? 'assertMatchesRegularExpression'
                        : 'assertRegExp';
                    $this->{$regMethod}(
                        '/"timestamp"\s*:\s*"\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}"/',
                        $subject
                    );
                    $this->{$regMethod}('/"url"\s*:\s*"\/"/', $subject);
                    $this->{$regMethod}('/"status"\s*:\s*"unknown"/', $subject);
                    $this->{$regMethod}('/"ttfb"\s*:\s*3.141592/', $subject);

                    return true;
                })
            );

        call_user_func($this->handler, $stats);
    }

    /**
     * Test stat handler curl stats.
     *
     * @return void
     */
    public function testStatHandlerCurlStats()
    {
        $mockUriInterface = $this->createMock(UriInterface::class);
        $mockUriInterface->expects($this->any())
            ->method('__toString')
            ->willReturn('/customer');
        $mockRequest = $this->createMock(RequestInterface::class);
        $mockRequest->expects($this->any())
            ->method('getUri')
            ->willReturn($mockUriInterface);
        $mockResponse = $this->createMock(ResponseInterface::class);

        $stats = new TransferStats($mockRequest, $mockResponse, 3.1415926, null, [CURLINFO_STARTTRANSFER_TIME => 0.62]);

        $mockResponse->expects($this->once())
            ->method('getStatusCode')
            ->willReturn(200);
        $this->loggerMock->expects($this->once())
            ->method('info')
            ->with('TTFB test result: 0.620s', ['url' => '/customer', 'status' => 200]);
        $this->fileListMock->method('getTtfbLog')
            ->willReturn('/path/to/ttfb.json');
        $this->fileMock->expects($this->once())
            ->method('isExists')
            ->with('/path/to/ttfb.json')
            ->willReturn(true);
        $this->fileMock->expects($this->once())
            ->method('fileGetContents')
            ->with('/path/to/ttfb.json')
            ->willReturn('[{"previous": "result"}]');
        $this->fileMock->expects($this->once())
            ->method('filePutContents')
            ->with(
                $this->equalTo('/path/to/ttfb.json'),
                $this->callBack(function (string $subject) {
                    $regMethod = method_exists($this, 'assertMatchesRegularExpression')
                        ? 'assertMatchesRegularExpression'
                        : 'assertRegExp';
                    $this->{$regMethod}('/\{\s*"previous"\s*:\s*"result"\s*\}/', $subject);
                    $this->{$regMethod}(
                        '/"timestamp"\s*:\s*"\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}"/',
                        $subject
                    );
                    $this->{$regMethod}('/"url"\s*:\s*"\/customer"/', $subject);
                    $this->{$regMethod}('/"status"\s*:\s*200/', $subject);
                    $this->{$regMethod}('/"ttfb"\s*:\s*0\.62/', $subject);

                    return true;
                })
            );

        call_user_func($this->handler, $stats);
    }
}
