<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Test\Unit\Http;

use GuzzleHttp\Client;
use Magento\MagentoCloud\App\ContainerInterface;
use Magento\MagentoCloud\Http\ClientFactory;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

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
     * @var ContainerInterface|MockObject
     */
    private $containerMock;

    /**
     * @var Client
     */
    private $clientMock;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->containerMock = $this->getMockForAbstractClass(ContainerInterface::class);
        $this->clientMock = $this->createMock(Client::class);

        $this->clientFactory = new ClientFactory(
            $this->containerMock
        );
    }

    public function testCreate(): void
    {
        $this->containerMock->expects($this->once())
            ->method('create')
            ->willReturn($this->clientMock);

        $this->clientFactory->create(['some' => 'value']);
    }
}
