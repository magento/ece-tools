<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Unit\Process\PostDeploy;

use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Pool;
use GuzzleHttp\Promise\PromiseInterface;
use Magento\MagentoCloud\Http\PoolFactory;
use Magento\MagentoCloud\Process\PostDeploy\WarmUp;
use Magento\MagentoCloud\Process\ProcessException;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Http\Message\RequestInterface;
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
    private $process;

    /**
     * @var PoolFactory|MockObject
     */
    private $poolFactoryMock;

    /**
     * @var Pool|MockObject
     */
    private $poolMock;

    /**
     * @var WarmUp\Urls|Mock
     */
    private $urlsMock;

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
    protected function setUp()
    {
        $this->poolFactoryMock = $this->createMock(PoolFactory::class);
        $this->poolMock = $this->createMock(Pool::class);
        $this->promiseMock = $this->createMock(PromiseInterface::class);
        $this->loggerMock = $this->createMock(LoggerInterface::class);
        $this->urlsMock = $this->createMock(WarmUp\Urls::class);

        $this->poolMock->method('promise')
            ->willReturn($this->promiseMock);

        $this->process = new WarmUp(
            $this->poolFactoryMock,
            $this->loggerMock,
            $this->urlsMock
        );
    }

    public function testExecute()
    {
        $urls = [
            'http://base-url.com/index.php',
            'http://base-url.com/index.php/customer/account/create'
        ];

        $this->urlsMock->method('getAll')
            ->willReturn($urls);

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

        $this->process->execute();
    }

    public function testExecuteWithPromiseException()
    {
        $urls = [
            'http://base-url.com/index.php',
            'http://base-url.com/index.php/customer/account/create'
        ];

        $this->urlsMock->expects($this->any())
            ->method('getAll')
            ->willReturn($urls);
        $this->poolFactoryMock->expects($this->once())
            ->method('create')
            ->with($this->equalTo($urls), $this->isType('array'))
            ->willReturn($this->poolMock);
        $this->promiseMock->expects($this->any())
            ->method('wait')
            ->willThrowException(new \Exception('some error'));

        $this->expectException(ProcessException::class);
        $this->expectExceptionMessage('some error');

        $this->process->execute();
    }
}
