<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Unit\Process\PostDeploy;

use GuzzleHttp\ClientInterface;
use GuzzleHttp\Promise\PromiseInterface;
use Magento\MagentoCloud\Config\Stage\PostDeployInterface;
use Magento\MagentoCloud\Http\ClientFactory;
use Magento\MagentoCloud\Http\RequestFactory;
use Magento\MagentoCloud\Process\PostDeploy\WarmUp;
use Magento\MagentoCloud\Util\UrlManager;
use PHPUnit\Framework\TestCase;
use PHPUnit_Framework_MockObject_MockObject as Mock;
use Psr\Http\Message\RequestInterface;
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
     * @var PostDeployInterface|Mock
     */
    private $postDeployMock;

    /**
     * @var ClientFactory|Mock
     */
    private $clientFactoryMock;

    /**
     * @var ClientInterface|Mock
     */
    private $clientMock;

    /**
     * @var RequestFactory|Mock
     */
    private $requestFactoryMock;

    /**
     * @var RequestInterface|Mock
     */
    private $requestMock;

    /**
     * @var UrlManager|Mock
     */
    private $urlManagerMock;

    /**
     * @var LoggerInterface|Mock
     */
    private $loggerMock;

    /**
     * @var PromiseInterface|Mock
     */
    private $promiseMock;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        $this->postDeployMock = $this->getMockForAbstractClass(PostDeployInterface::class);
        $this->clientFactoryMock = $this->createMock(ClientFactory::class);
        $this->urlManagerMock = $this->createMock(UrlManager::class);
        $this->loggerMock = $this->getMockForAbstractClass(LoggerInterface::class);
        $this->clientMock = $this->getMockForAbstractClass(ClientInterface::class);
        $this->requestFactoryMock = $this->createMock(RequestFactory::class);
        $this->requestMock = $this->getMockForAbstractClass(RequestInterface::class);
        $this->promiseMock = $this->getMockForAbstractClass(PromiseInterface::class);

        $this->clientFactoryMock->expects($this->any())
            ->method('create')
            ->willReturn($this->clientMock);
        $this->requestFactoryMock->expects($this->any())
            ->method('create')
            ->willReturn($this->requestMock);

        $this->process = new WarmUp(
            $this->postDeployMock,
            $this->clientFactoryMock,
            $this->requestFactoryMock,
            $this->urlManagerMock,
            $this->loggerMock
        );
    }

    public function testExecute()
    {
        $this->postDeployMock->expects($this->once())
            ->method('get')
            ->with(PostDeployInterface::VAR_WARM_UP_PAGES)
            ->willReturn([
                'index.php',
                'index.php/customer/account/create',
            ]);
        $this->requestFactoryMock->expects($this->exactly(2))
            ->method('create')
            ->withConsecutive(
                ['GET', 'http://base-url.com/index.php'],
                ['GET', 'http://base-url.com/index.php/customer/account/create']
            );
        $this->urlManagerMock->expects($this->any())
            ->method('getBaseUrl')
            ->willReturn('http://base-url.com/');
        $this->urlManagerMock->expects($this->never())
            ->method('getBaseUrls');
        $this->clientMock->expects($this->exactly(2))
            ->method('sendAsync')
            ->with($this->requestMock)
            ->willReturn($this->promiseMock);
        $this->promiseMock->expects($this->exactly(2))
            ->method('then')
            ->willReturn($this->promiseMock);

        $this->process->execute();
    }

    public function testExecuteWithHttpUrls()
    {
        $this->postDeployMock->expects($this->once())
            ->method('get')
            ->with(PostDeployInterface::VAR_WARM_UP_PAGES)
            ->willReturn([
                'index.php',
                'http://example.com/products/',
                'http://example2.com/products/',
                'http://example3.com/products/',
                'http://example4.com/products/',
            ]);
        $this->requestFactoryMock->expects($this->exactly(3))
            ->method('create')
            ->withConsecutive(
                ['GET', 'http://base-url.com/index.php'],
                ['GET', 'http://example.com/products/'],
                ['GET', 'http://example3.com/products/']
            );
        $this->urlManagerMock->expects($this->any())
            ->method('getBaseUrl')
            ->willReturn('http://base-url.com/');
        $this->urlManagerMock->expects($this->once())
            ->method('getBaseUrls')
            ->willReturn([
                'http://example.com/',
                'http://example3.com/'
            ]);
        $this->loggerMock->expects($this->exactly(2))
            ->method('error')
            ->withConsecutive(
                [$this->stringStartsWith('Page "http://example2.com/products/" can\'t be warmed-up')],
                [$this->stringStartsWith('Page "http://example4.com/products/" can\'t be warmed-up')]
            );
        $this->clientMock->expects($this->exactly(3))
            ->method('sendAsync')
            ->with($this->requestMock)
            ->willReturn($this->promiseMock);
        $this->promiseMock->expects($this->exactly(3))
            ->method('then')
            ->willReturn($this->promiseMock);

        $this->process->execute();
    }
}
