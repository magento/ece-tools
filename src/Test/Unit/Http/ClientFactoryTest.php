<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Unit\Http;

use GuzzleHttp\Client;
use Magento\MagentoCloud\App\ContainerInterface;
use Magento\MagentoCloud\Http\ClientFactory;
use PHPUnit\Framework\TestCase;
use PHPUnit_Framework_MockObject_MockObject as Mock;

/**
 * @inheritdoc
 */
class ClientFactoryTest extends TestCase
{
    /**
     * @var ClientFactory
     */
    private $clientFactory;

    /**
     * @var ContainerInterface|Mock
     */
    private $containerMock;

    /**
     * @var Client
     */
    private $clientMock;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        $this->containerMock = $this->getMockForAbstractClass(ContainerInterface::class);
        $this->clientMock = $this->createMock(Client::class);

        $this->clientFactory = new ClientFactory(
            $this->containerMock
        );
    }

    public function testCreate()
    {
        $this->containerMock->expects($this->once())
            ->method('create')
            ->willReturn($this->clientMock);

        $this->assertInstanceOf(
            Client::class,
            $this->clientFactory->create(['some' => 'value'])
        );
    }
}
