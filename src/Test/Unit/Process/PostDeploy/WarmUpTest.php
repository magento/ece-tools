<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Unit\Process\PostDeploy;

use GuzzleHttp\ClientInterface;
use Magento\MagentoCloud\Http\ClientFactory;
use Magento\MagentoCloud\Process\PostDeploy\WarmUp;
use Magento\MagentoCloud\Util\UrlManager;
use PHPUnit\Framework\TestCase;
use PHPUnit_Framework_MockObject_MockObject as Mock;
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
     * @var ClientFactory|Mock
     */
    private $clientFactoryMock;

    /**
     * @var ClientInterface|Mock
     */
    private $clientMock;

    /**
     * @var ResponseInterface|Mock
     */
    private $responseMock;

    /**
     * @var UrlManager|Mock
     */
    private $urlManagerMock;

    /**
     * @var LoggerInterface|Mock
     */
    private $loggerMock;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        $this->clientFactoryMock = $this->createMock(ClientFactory::class);
        $this->urlManagerMock = $this->createMock(UrlManager::class);
        $this->loggerMock = $this->getMockForAbstractClass(LoggerInterface::class);
        $this->clientMock = $this->getMockForAbstractClass(ClientInterface::class);
        $this->responseMock = $this->getMockForAbstractClass(ResponseInterface::class);

        $this->clientFactoryMock->expects($this->any())
            ->method('create')
            ->willReturn($this->clientMock);

        $this->process = new WarmUp(
            $this->clientFactoryMock,
            $this->urlManagerMock,
            $this->loggerMock
        );
    }

    public function testExecute()
    {
        $this->urlManagerMock->expects($this->any())
            ->method('getDefaultSecureUrl')
            ->willReturn('site_url/');
        $this->responseMock->expects($this->any())
            ->method('getStatusCode')
            ->willReturn(200);
        $this->clientMock->expects($this->exactly(2))
            ->method('request')
            ->withConsecutive(
                ['GET', 'site_url/index.php'],
                ['GET', 'site_url/index.php/customer/account/create']
            )
            ->willReturn($this->responseMock);

        $this->loggerMock->expects($this->exactly(2))
            ->method('info')
            ->withConsecutive(
                ['Warming up page: site_url/index.php', ['code' => 200]],
                ['Warming up page: site_url/index.php/customer/account/create', ['code' => 200]]
            );

        $this->process->execute();
    }
}
