<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Test\Unit\Step\PostDeploy;

use GuzzleHttp\Pool;
use GuzzleHttp\Promise\PromiseInterface;
use GuzzleHttp\RequestOptions;
use Magento\MagentoCloud\App\Error;
use Magento\MagentoCloud\Config\Stage\PostDeployInterface;
use Magento\MagentoCloud\Http\PoolFactory;
use Magento\MagentoCloud\Http\TransferStatsHandler;
use Magento\MagentoCloud\Step\PostDeploy\TimeToFirstByte;
use Magento\MagentoCloud\Step\StepException;
use Magento\MagentoCloud\Util\UrlManager;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;

/**
 * @inheritdoc
 */
class TimeToFirstByteTest extends TestCase
{
    /**
     * @var PostDeployInterface|MockObject
     */
    private $postDeployMock;

    /**
     * @var PoolFactory|MockObject
     */
    private $poolFactoryMock;

    /**
     * @var UrlManager|MockObject
     */
    private $urlManagerMock;

    /**
     * @var TransferStatsHandler|MockObject
     */
    private $statHandlerMock;

    /**
     * @var LoggerInterface|MockObject
     */
    private $loggerMock;

    /**
     * @var Pool|MockObject
     */
    private $poolMock;

    /**
     * @var PromiseInterface|MockObject
     */
    private $promiseMock;

    /**
     * @var TimeToFirstByte
     */
    private $step;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->postDeployMock = $this->createMock(PostDeployInterface::class);
        $this->poolFactoryMock = $this->createMock(PoolFactory::class);
        $this->urlManagerMock = $this->createMock(UrlManager::class);
        $this->statHandlerMock = $this->createMock(TransferStatsHandler::class);
        $this->loggerMock = $this->createMock(LoggerInterface::class);
        $this->poolMock = $this->createMock(Pool::class);
        $this->promiseMock = $this->createMock(PromiseInterface::class);

        $this->poolMock->method('promise')
            ->willReturn($this->promiseMock);

        $this->step = new TimeToFirstByte(
            $this->postDeployMock,
            $this->poolFactoryMock,
            $this->urlManagerMock,
            $this->statHandlerMock,
            $this->loggerMock
        );
    }

    public function testExecute()
    {
        $this->postDeployMock->expects($this->once())
            ->method('get')
            ->with(PostDeployInterface::VAR_TTFB_TESTED_PAGES)
            ->willReturn(['/', '/customer/account/create', 'https://example.com']);
        $this->urlManagerMock->method('isUrlValid')
            ->willReturnMap([
                ['/', true],
                ['/customer/account/create', true],
                ['https://example.com', false],
            ]);
        $this->poolFactoryMock->expects($this->once())
            ->method('create')
            ->with(
                ['/', '/customer/account/create'],
                ['options' => [RequestOptions::ON_STATS => $this->statHandlerMock], 'concurrency' => 1]
            )->willReturn($this->poolMock);
        $this->promiseMock->expects($this->once())
            ->method('wait');

        $this->step->execute();
    }

    public function testExecuteWithException(): void
    {
        $this->postDeployMock->expects($this->once())
            ->method('get')
            ->with(PostDeployInterface::VAR_TTFB_TESTED_PAGES)
            ->willReturn(['/']);
        $this->urlManagerMock->method('isUrlValid')
            ->willReturn(true);
        $this->poolFactoryMock->expects($this->once())
            ->method('create')
            ->with(
                ['/'],
                ['options' => [RequestOptions::ON_STATS => $this->statHandlerMock], 'concurrency' => 1]
            )->willReturn($this->poolMock);
        $this->promiseMock->expects($this->once())
            ->method('wait')
            ->willThrowException(new \Exception('Promise exception'));

        $this->expectException(StepException::class);
        $this->expectExceptionMessage('Promise exception');
        $this->expectExceptionCode(Error::PD_DURING_TIME_TO_FIRST_BYTE);

        $this->step->execute();
    }
}
