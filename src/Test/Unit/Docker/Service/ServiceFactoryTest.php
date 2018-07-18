<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Unit\Docker\Service;

use Magento\MagentoCloud\App\ContainerInterface;
use Magento\MagentoCloud\Docker\Service\RedisService;
use Magento\MagentoCloud\Docker\Service\ServiceFactory;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @inheritdoc
 */
class ServiceFactoryTest extends TestCase
{
    /**
     * @var ServiceFactory
     */
    private $factory;

    /**
     * @var ContainerInterface|MockObject
     */
    private $containerMock;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        $this->containerMock = $this->getMockForAbstractClass(ContainerInterface::class);

        $this->factory = new ServiceFactory(
            $this->containerMock
        );
    }

    public function testCreate()
    {
        $this->containerMock->expects($this->once())
            ->method('create')
            ->with(RedisService::class)
            ->willReturn($this->createMock(RedisService::class));

        $this->factory->create(ServiceFactory::SERVICE_REDIS);
    }
}
