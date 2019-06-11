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
use Magento\MagentoCloud\Package\Manager;
use Magento\MagentoCloud\Process\PostDeploy\WarmUp;
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
     * @var WarmUp\Urls|Mock
     */
    private $urlsMock;

    /**
     * @var LoggerInterface|Mock
     */
    private $loggerMock;

    /**
     * @var PromiseInterface|Mock
     */
    private $promiseMock;

    /**
     * @var Manager|Mock
     */
    private $packageManagerMock;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        $this->postDeployMock = $this->getMockForAbstractClass(PostDeployInterface::class);
        $this->packageManagerMock = $this->createMock(Manager::class);
        $this->clientFactoryMock = $this->createMock(ClientFactory::class);
        $this->urlsMock = $this->createMock(WarmUp\Urls::class);
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
            $this->clientFactoryMock,
            $this->requestFactoryMock,
            $this->loggerMock,
            $this->urlsMock,
            $this->packageManagerMock,
            $this->postDeployMock
        );
    }

    public function testExecuteVariableNotConfigured()
    {
        $this->postDeployMock->expects($this->once())
            ->method('get')
            ->with(PostDeployInterface::VAR_WARM_UP_PAGES)
            ->willReturn([]);
        $this->loggerMock->expects($this->once())
            ->method('info')
            ->with('Skipping the warm-up phase because the WARM_UP_PAGES variable is not configured.');
        $this->clientMock->expects($this->never())
            ->method('sendAsync');
        $this->packageManagerMock->expects($this->never())
            ->method('has');

        $this->process->execute();
    }

    public function testExecuteVModuleNOtInstalled()
    {
        $this->packageManagerMock->expects($this->once())
            ->method('has')
            ->with('magento/magento-cloud-components', '*')
            ->willReturn(false);
        $this->postDeployMock->expects($this->once())
            ->method('get')
            ->with(PostDeployInterface::VAR_WARM_UP_PAGES)
            ->willReturn(['some_url']);
        $this->loggerMock->expects($this->once())
            ->method('warning')
            ->with('Skipping the warm-up phase because `magento/magento-cloud-components` is not installed.');
        $this->loggerMock->expects($this->never())
            ->method('info');
        $this->clientMock->expects($this->never())
            ->method('sendAsync');

        $this->process->execute();
    }


    public function testExecute()
    {
        $this->postDeployMock->expects($this->once())
            ->method('get')
            ->with(PostDeployInterface::VAR_WARM_UP_PAGES)
            ->willReturn(['some_url']);
        $this->packageManagerMock->expects($this->once())
            ->method('has')
            ->with('magento/magento-cloud-components', '*')
            ->willReturn(true);
        $this->urlsMock->expects($this->any())
            ->method('getAll')
            ->willReturn([
                'http://base-url.com/index.php',
                'http://base-url.com/index.php/customer/account/create'
            ]);
        $this->requestFactoryMock->expects($this->exactly(2))
            ->method('create')
            ->withConsecutive(
                ['GET', 'http://base-url.com/index.php'],
                ['GET', 'http://base-url.com/index.php/customer/account/create']
            );
        $this->clientMock->expects($this->exactly(2))
            ->method('sendAsync')
            ->with($this->requestMock)
            ->willReturn($this->promiseMock);
        $this->promiseMock->expects($this->exactly(2))
            ->method('then')
            ->willReturn($this->promiseMock);
        $this->promiseMock->expects($this->exactly(2))
            ->method('wait');

        $this->process->execute();
    }

    /**
     * @expectedException \Magento\MagentoCloud\Process\ProcessException
     * @expectedExceptionMessage some error
     */
    public function testExecuteWithPromiseException()
    {
        $this->postDeployMock->expects($this->once())
            ->method('get')
            ->with(PostDeployInterface::VAR_WARM_UP_PAGES)
            ->willReturn(['some_url']);
        $this->packageManagerMock->expects($this->once())
            ->method('has')
            ->with('magento/magento-cloud-components', '*')
            ->willReturn(true);
        $this->urlsMock->expects($this->any())
            ->method('getAll')
            ->willReturn([
                'http://base-url.com/index.php',
                'http://base-url.com/index.php/customer/account/create'
            ]);
        $this->requestFactoryMock->expects($this->exactly(2))
            ->method('create')
            ->withConsecutive(
                ['GET', 'http://base-url.com/index.php'],
                ['GET', 'http://base-url.com/index.php/customer/account/create']
            );
        $this->clientMock->expects($this->exactly(2))
            ->method('sendAsync')
            ->with($this->requestMock)
            ->willReturn($this->promiseMock);
        $this->promiseMock->expects($this->exactly(2))
            ->method('then')
            ->willReturn($this->promiseMock);
        $this->promiseMock->expects($this->any())
            ->method('wait')
            ->willThrowException(new \Exception('some error'));

        $this->process->execute();
    }
}
