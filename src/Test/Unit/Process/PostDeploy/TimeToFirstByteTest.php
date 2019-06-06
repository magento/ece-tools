<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\MagentoCloud\Test\Unit\Process\PostDeploy;


use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Pool;
use GuzzleHttp\Promise\PromiseInterface;
use GuzzleHttp\RequestOptions;
use GuzzleHttp\TransferStats;
use Magento\MagentoCloud\Config\Stage\PostDeployInterface;
use Magento\MagentoCloud\Filesystem\Driver\File;
use Magento\MagentoCloud\Filesystem\FileList;
use Magento\MagentoCloud\Http\PoolFactory;
use Magento\MagentoCloud\Process\PostDeploy\TimeToFirstByte;
use Magento\MagentoCloud\Util\UrlManager;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;

/**
 * @inheritdoc
 */
class TimeToFirstByteTest extends TestCase
{
    /** @var PostDeployInterface|MockObject */
    private $postDeployMock;

    /** @var PoolFactory|MockObject */
    private $poolFactoryMock;

    /** @var UrlManager|MockObject */
    private $urlManagerMock;

    /** @var LoggerInterface|MockObject */
    private $loggerMock;

    /** @var FileList|MockObject */
    private $fileListMock;

    /** @var File|MockObject */
    private $fileMock;

    /** @var Pool|MockObject */
    private $poolMock;

    /** @var PromiseInterface|MockObject */
    private $promiseMock;

    /** @var TimeToFirstByte */
    private $process;

    protected function setUp()
    {
        $this->postDeployMock = $this->createMock(PostDeployInterface::class);
        $this->poolFactoryMock = $this->createMock(PoolFactory::class);
        $this->urlManagerMock = $this->createMock(UrlManager::class);
        $this->loggerMock = $this->createMock(LoggerInterface::class);
        $this->fileListMock = $this->createMock(FileList::class);
        $this->fileMock = $this->createMock(File::class);
        $this->poolMock = $this->createMock(Pool::class);
        $this->promiseMock = $this->createMock(PromiseInterface::class);

        $this->poolMock->method('promise')
            ->willReturn($this->promiseMock);

        $this->process = new TimeToFirstByte(
            $this->postDeployMock,
            $this->poolFactoryMock,
            $this->urlManagerMock,
            $this->loggerMock,
            $this->fileListMock,
            $this->fileMock
        );
    }

    public function testGetUrlsForTesting()
    {
        $this->postDeployMock->expects($this->once())
            ->method('get')
            ->with(PostDeployInterface::VAR_TTFB_TESTED_PAGES)
            ->willReturn([
                'index.php',
                'https://example.com/products/',
                'https://example2.com/products/',
            ]);
        $this->urlManagerMock->expects($this->exactly(3))
            ->method('isUrlValid')
            ->willReturnMap([
                ['index.php', true],
                ['https://example.com/products/', true],
                ['https://example2.com/products/', false]
            ]);
        $this->loggerMock->expects($this->once())
            ->method('error')
            ->with('Will not test https://example2.com/products/, host is not a configured store domain');

        $this->assertSame(['index.php', 'https://example.com/products/'], $this->process->getUrlsForTesting());
    }

    public function testExecuteTtfbDisabled()
    {
        $this->postDeployMock->expects($this->once())
            ->method('get')
            ->with(PostDeployInterface::VAR_ENABLE_TTFB_TEST)
            ->willReturn(false);
        $this->loggerMock->expects($this->once())
            ->method('debug')
            ->with('Time to first byte testing has been disabled.');
        $this->poolFactoryMock->expects($this->never())
            ->method('create');

        $this->process->execute();
    }

    public function testExecute()
    {
        $urls = ['/', '/customer/account/create'];

        $this->postDeployMock->expects($this->exactly(2))
            ->method('get')
            ->willReturnMap([
                [PostDeployInterface::VAR_ENABLE_TTFB_TEST, true],
                [PostDeployInterface::VAR_TTFB_TESTED_PAGES, $urls],
            ]);
        $this->urlManagerMock->method('isUrlValid')
            ->willReturn(true);
        $this->poolFactoryMock->expects($this->once())
            ->method('create')
            ->with(
                $urls,
                ['options' => [RequestOptions::ON_STATS => [$this->process, 'statHandler']], 'concurrency' => 1]
            )->willReturn($this->poolMock);
        $this->promiseMock->expects($this->once())
            ->method('wait');

        $this->process->execute();
    }

    public function testStatHandlerRedirect()
    {
        $mockRequest = $this->createMock(RequestInterface::class);
        $mockResponse = $this->createMock(ResponseInterface::class);

        $stats = new TransferStats($mockRequest, $mockResponse);

        $mockResponse->expects($this->once())
            ->method('getStatusCode')
            ->willReturn(302);
        $this->loggerMock->expects($this->once())
            ->method('debug')
            ->with('TTFB response was a redirect');

        $this->process->statHandler($stats);
    }

    public function testStatHandlerTransferTime()
    {
        $mockRequest = $this->createMock(RequestInterface::class);

        $stats = new TransferStats($mockRequest, null, 3.1415926);

        $mockRequest->method('getUri')
            ->wilLReturn('/');
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
                    $this->assertRegExp('/"timestamp"\s*:\s*"\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}"/', $subject);
                    $this->assertRegExp('/"url"\s*:\s*"\/"/', $subject);
                    $this->assertRegExp('/"status"\s*:\s*"unknown"/', $subject);
                    $this->assertRegExp('/"ttfb"\s*:\s*3.141592/', $subject);

                    return true;
                })
            );

        $this->process->statHandler($stats);
    }

    public function testStatHandlerCurlStats()
    {
        $mockRequest = $this->createMock(RequestInterface::class);
        $mockResponse = $this->createMock(ResponseInterface::class);

        $stats = new TransferStats($mockRequest, $mockResponse, 3.1415926, null, [CURLINFO_STARTTRANSFER_TIME => 0.62]);

        $mockRequest->method('getUri')
            ->wilLReturn('/customer');
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
                    $this->assertRegExp('/\{\s*"previous"\s*:\s*"result"\s*\}/', $subject);
                    $this->assertRegExp('/"timestamp"\s*:\s*"\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}"/', $subject);
                    $this->assertRegExp('/"url"\s*:\s*"\/customer"/', $subject);
                    $this->assertRegExp('/"status"\s*:\s*200/', $subject);
                    $this->assertRegExp('/"ttfb"\s*:\s*0\.62/', $subject);

                    return true;
                })
            );

        $this->process->statHandler($stats);
    }
}
