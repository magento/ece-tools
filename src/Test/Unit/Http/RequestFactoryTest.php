<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Test\Unit\Http;

use Magento\MagentoCloud\App\ContainerInterface;
use Magento\MagentoCloud\Http\RequestFactory;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestInterface;

/**
 * @inheritdoc
 */
class RequestFactoryTest extends TestCase
{
    /**
     * @var RequestFactory
     */
    private $requestFactory;

    /**
     * @var ContainerInterface|MockObject
     */
    private $containerMock;

    /**
     * @var RequestInterface
     */
    private $requestMock;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->containerMock = $this->getMockForAbstractClass(ContainerInterface::class);
        $this->requestMock = $this->getMockForAbstractClass(RequestInterface::class);

        $this->requestFactory = new RequestFactory(
            $this->containerMock
        );
    }

    public function testCreate(): void
    {
        $this->containerMock->expects($this->once())
            ->method('create')
            ->willReturn($this->requestMock);

        $this->requestFactory->create('GET', 'some_uri');
    }
}
