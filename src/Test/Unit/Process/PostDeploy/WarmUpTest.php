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
        $this->urlManagerMock->expects($this->any())
            ->method('getBaseUrl')
            ->willReturn('site_url/');
        $this->clientMock->expects($this->exactly(2))
            ->method('sendAsync')
            ->with($this->requestMock)
            ->willReturn($this->promiseMock);
        $this->promiseMock->expects($this->exactly(2))
            ->method('then')
            ->willReturn($this->promiseMock);

        $this->process->execute();
    }
}
