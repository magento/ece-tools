<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Test\Unit\Http;

use GuzzleHttp\Client;
use GuzzleHttp\Pool;
use Magento\MagentoCloud\App\ContainerInterface;
use Magento\MagentoCloud\Http\ClientFactory;
use Magento\MagentoCloud\Http\PoolFactory;
use Magento\MagentoCloud\Http\RequestFactory;
use Magento\MagentoCloud\Util\UrlManager;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Http\Message\RequestInterface;

/**
 * {@inheritdoc}
 */
class PoolFactoryTest extends TestCase
{
    /**
     * @var ContainerInterface|MockObject
     */
    private $containerMock;

    /**
     * @var ClientFactory|MockObject
     */
    private $clientFactoryMock;

    /**
     * @var RequestFactory|MockObject
     */
    private $requestFactoryMock;

    /**
     * @var UrlManager|MockObject
     */
    private $urlManagerMock;

    /**
     * @var PoolFactory
     */
    private $poolFactory;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->containerMock = $this->createMock(ContainerInterface::class);
        $this->clientFactoryMock = $this->createMock(ClientFactory::class);
        $this->requestFactoryMock = $this->createMock(RequestFactory::class);
        $this->urlManagerMock = $this->createMock(UrlManager::class);

        $this->poolFactory = new PoolFactory(
            $this->containerMock,
            $this->clientFactoryMock,
            $this->requestFactoryMock,
            $this->urlManagerMock
        );
    }

    public function testCreate()
    {
        $clientMock = $this->createMock(Client::class);
        $poolMock = $this->createMock(Pool::class);
        $requestMock = $this->createMock(RequestInterface::class);

        $this->clientFactoryMock->expects($this->once())
            ->method('create')
            ->with(['client' => 'options'])
            ->willReturn($clientMock);
        $this->urlManagerMock->expects($this->exactly(2))
            ->method('expandUrl')
            ->willReturnMap([
                ['/', 'https://example.com/'],
                ['foo/bar', 'https://example.com/foo/bar']
            ]);
        $this->requestFactoryMock->expects($this->exactly(2))
            ->method('create')
            ->willReturnMap([
                ['GET', 'https://example.com/', $requestMock],
                ['GET', 'https://example.com/foo/bar', $requestMock],
            ]);
        $this->containerMock->expects($this->once())
            ->method('create')
            ->with(
                $this->equalTo(Pool::class),
                $this->callBack(function (array $subject) use ($clientMock, $requestMock) {
                    return array_key_exists('client', $subject)
                        && array_key_exists('requests', $subject)
                        && array_key_exists('config', $subject)
                        && $subject['client'] === $clientMock
                        && count($subject['requests']) === 2
                        && $subject['requests'][0] === $requestMock
                        && $subject['requests'][1] === $requestMock
                        && $subject['config'] === ['request' => 'options'];
                })
            )->willReturn($poolMock);

        $this->assertSame(
            $poolMock,
            $this->poolFactory->create(['/', 'foo/bar'], ['request' => 'options'], ['client' => 'options'])
        );
    }
}
