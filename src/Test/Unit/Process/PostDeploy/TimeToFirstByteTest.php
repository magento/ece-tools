<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\MagentoCloud\Test\Unit\Process\PostDeploy;

use GuzzleHttp\Pool;
use GuzzleHttp\Promise\PromiseInterface;
use GuzzleHttp\RequestOptions;
use Magento\MagentoCloud\Config\Stage\PostDeployInterface;
use Magento\MagentoCloud\Http\PoolFactory;
use Magento\MagentoCloud\Http\TransferStatsHandler;
use Magento\MagentoCloud\Process\PostDeploy\TimeToFirstByte;
use Magento\MagentoCloud\Util\UrlManager;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
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

    /** @var TransferStatsHandler|MockObject */
    private $statHandlerMock;

    /** @var LoggerInterface|MockObject */
    private $loggerMock;

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
        $this->statHandler = $this->createMock(TransferStatsHandler::class);
        $this->loggerMock = $this->createMock(LoggerInterface::class);
        $this->poolMock = $this->createMock(Pool::class);
        $this->promiseMock = $this->createMock(PromiseInterface::class);

        $this->poolMock->method('promise')
            ->willReturn($this->promiseMock);

        $this->process = new TimeToFirstByte(
            $this->postDeployMock,
            $this->poolFactoryMock,
            $this->urlManagerMock,
            $this->statHandler,
            $this->loggerMock
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
                ['options' => [RequestOptions::ON_STATS => $this->statHandler], 'concurrency' => 1]
            )->willReturn($this->poolMock);
        $this->promiseMock->expects($this->once())
            ->method('wait');

        $this->process->execute();
    }
}
