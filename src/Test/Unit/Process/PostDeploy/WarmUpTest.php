<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Unit\Process\PostDeploy;

use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Pool;
use GuzzleHttp\Promise\PromiseInterface;
use Magento\MagentoCloud\Config\Stage\PostDeployInterface;
use Magento\MagentoCloud\Http\PoolFactory;
use Magento\MagentoCloud\Process\PostDeploy\WarmUp;
use Magento\MagentoCloud\Util\UrlManager;
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
     * @var PostDeployInterface|MockObject
     */
    private $postDeployMock;

    /**
     * @var PoolFactory|MockObject
     */
    private $poolFactoryMock;

    /**
     * @var Pool|MockObject
     */
    private $poolMock;

    /**
     * @var UrlManager|MockObject
     */
    private $urlManagerMock;

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
        $this->postDeployMock = $this->createMock(PostDeployInterface::class);
        $this->poolFactoryMock = $this->createMock(PoolFactory::class);
        $this->urlManagerMock = $this->createMock(UrlManager::class);
        $this->loggerMock = $this->createMock(LoggerInterface::class);
        $this->poolMock = $this->createMock(Pool::class);
        $this->promiseMock = $this->createMock(PromiseInterface::class);

        $this->poolMock->method('promise')
            ->willReturn($this->promiseMock);

        $this->process = new WarmUp(
            $this->postDeployMock,
            $this->poolFactoryMock,
            $this->urlManagerMock,
            $this->loggerMock
        );
    }

    public function testExecute()
    {
        $urls = [
            'index.php',
            'index.php/customer/account/create',
        ];

        $mockResponse = $this->createMock(ResponseInterface::class);
        $mockException = $this->createMock(RequestException::class);

        $mockException->method('getResponse')
            ->willReturn($mockResponse);
        $mockResponse->method('getStatusCode')
            ->willReturn(503);
        $mockResponse->method('getReasonPhrase')
            ->willReturn('Service Unavailable');

        $this->postDeployMock->expects($this->once())
            ->method('get')
            ->with(PostDeployInterface::VAR_WARM_UP_PAGES)
            ->willReturn($urls);
        $this->urlManagerMock->expects($this->atLeastOnce())
            ->method('isUrlValid')
            ->willReturn(true);
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
            ->method('info')
            ->with('Warmed up page: index.php');
        $this->loggerMock->expects($this->once())
            ->method('error')
            ->with(
                'Warming up failed: index.php/customer/account/create',
                ['error' => 'Service Unavailable', 'code' => 503]
            );

        $this->process->execute();
    }

    public function testGetUrlsForWarmUp($value='')
    {
        $this->postDeployMock->expects($this->once())
            ->method('get')
            ->with(PostDeployInterface::VAR_WARM_UP_PAGES)
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
            ->with('Page "https://example2.com/products/" can\'t be warmed-up because such domain is not registered in current Magento installation');

        $this->assertSame(['index.php', 'https://example.com/products/'], $this->process->getUrlsForWarmUp());
    }
}
