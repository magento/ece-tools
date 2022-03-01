<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Test\Unit\Step\PostDeploy;

use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Pool;
use GuzzleHttp\Promise\PromiseInterface;
use Magento\MagentoCloud\App\Error;
use Magento\MagentoCloud\Config\Stage\PostDeployInterface;
use Magento\MagentoCloud\Http\PoolFactory;
use Magento\MagentoCloud\Step\PostDeploy\WarmUp;
use Magento\MagentoCloud\Step\StepException;
use Magento\MagentoCloud\WarmUp\Urls;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;

/**
 * @inheritdoc
 */
class WarmUpTest extends TestCase
{
    /**
     * @var WarmUp
     */
    private $step;

    /**
     * @var PoolFactory|MockObject
     */
    private $poolFactoryMock;

    /**
     * @var Pool|MockObject
     */
    private $poolMock;

    /**
     * @var Urls|MockObject
     */
    private $urlsMock;

    /**
     * @var PostDeployInterface|MockObject
     */
    private $postDeployMock;

    /**
     * @var LoggerInterface|MockObject
     */
    private $loggerMock;

    /**
     * @var PromiseInterface|MockObject
     */
    private $promiseMock;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->poolFactoryMock = $this->createMock(PoolFactory::class);
        $this->poolMock = $this->createMock(Pool::class);
        $this->promiseMock = $this->createMock(PromiseInterface::class);
        $this->loggerMock = $this->createMock(LoggerInterface::class);
        $this->urlsMock = $this->createMock(Urls::class);
        $this->postDeployMock = $this->createMock(PostDeployInterface::class);

        $this->poolMock->method('promise')
            ->willReturn($this->promiseMock);

        $this->step = new WarmUp(
            $this->poolFactoryMock,
            $this->loggerMock,
            $this->urlsMock,
            $this->postDeployMock
        );
    }

    /**
     * @throws StepException
     */
    public function testExecute()
    {
        $urls = [
            'http://base-url.com/index.php',
            'http://base-url.com/index.php/customer/account/create'
        ];
        $concurrency = 0;

        $this->urlsMock->method('getAll')
            ->willReturn($urls);
        $this->postDeployMock->method('get')
            ->with(PostDeployInterface::VAR_WARM_UP_CONCURRENCY)
            ->willReturn($concurrency);

        $mockResponse = $this->createMock(ResponseInterface::class);
        $mockException = $this->createMock(RequestException::class);

        $mockException->method('getResponse')
            ->willReturn($mockResponse);
        $mockResponse->method('getStatusCode')
            ->willReturn(503);
        $mockResponse->method('getReasonPhrase')
            ->willReturn('Service Unavailable');

        $this->poolFactoryMock->expects($this->once())
            ->method('create')
            ->with(
                $this->equalTo($urls),
                $this->callBack(function (array $subject) use ($mockResponse, $mockException) {
                    if (array_key_exists('fulfilled', $subject) && array_key_exists('rejected', $subject)
                        && is_callable($subject['fulfilled']) && is_callable($subject['rejected'])
                        && array_key_exists('concurrency', $subject) === false
                    ) {
                        $subject['fulfilled']($mockResponse, 0);
                        $subject['rejected']($mockException, 1);

                        return true;
                    }

                    return false;
                })
            )->willReturn($this->poolMock);
        $this->promiseMock->expects($this->once())
            ->method('wait');
        $this->loggerMock->expects($this->once())
            ->method('error')
            ->with(
                'Warming up failed: http://base-url.com/index.php/customer/account/create',
                ['error' => 'Service Unavailable', 'code' => 503]
            );

        $this->step->execute();
    }

    /**
     * @throws StepException
     */
    public function testExecuteWithPromiseException()
    {
        $urls = [
            'http://base-url.com/index.php',
            'http://base-url.com/index.php/customer/account/create'
        ];
        $concurrency = 0;

        $this->urlsMock->expects($this->any())
            ->method('getAll')
            ->willReturn($urls);
        $this->postDeployMock->method('get')
            ->with(PostDeployInterface::VAR_WARM_UP_CONCURRENCY)
            ->willReturn($concurrency);

        $this->poolFactoryMock->expects($this->once())
            ->method('create')
            ->with($this->equalTo($urls), $this->isType('array'))
            ->willReturn($this->poolMock);
        $this->promiseMock->expects($this->any())
            ->method('wait')
            ->willThrowException(new \Exception('some error'));

        $this->expectException(StepException::class);
        $this->expectExceptionMessage('some error');
        $this->expectExceptionCode(Error::PD_DURING_PAGE_WARM_UP);

        $this->step->execute();
    }

    /**
     * @throws StepException
     */
    public function testExecuteWithConcurrency()
    {
        $urls = [
            'http://base-url.com/index.php',
            'http://base-url.com/index.php/customer/account/create'
        ];
        $concurrency = 2;

        $this->loggerMock->expects($this->any())
            ->method('info')
            ->withConsecutive(
                ['Starting page warmup'],
                ['Warmup concurrency set to ' . $concurrency . ' as specified by the '
                    . PostDeployInterface::VAR_WARM_UP_CONCURRENCY . ' configuration']
            );

        $this->urlsMock->method('getAll')
            ->willReturn($urls);
        $this->postDeployMock->method('get')
            ->with(PostDeployInterface::VAR_WARM_UP_CONCURRENCY)
            ->willReturn($concurrency);

        $this->poolFactoryMock->expects($this->once())
            ->method('create')
            ->with(
                $this->equalTo($urls),
                $this->callBack(function (array $subject) use ($concurrency) {
                    return array_key_exists('concurrency', $subject) && $subject['concurrency'] === $concurrency;
                })
            )->willReturn($this->poolMock);
        $this->promiseMock->expects($this->once())
            ->method('wait');

        $this->step->execute();
    }
}
