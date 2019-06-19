<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Test\Unit\Service;

use Codeception\PHPUnit\TestCase;
use Magento\MagentoCloud\App\ContainerInterface;
use Magento\MagentoCloud\Service\ServiceFactory;
use Magento\MagentoCloud\Service\ServiceInterface;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * @inheritdoc
 */
class ServiceFactoryTest extends TestCase
{
    /**
     * @var ServiceFactory
     */
    private $serviceFactory;

    /**
     * @var ContainerInterface|MockObject
     */
    private $containerMock;

    /**
     * @inheritdoc
     */
    public function setUp()
    {
        $this->containerMock = $this->getMockForAbstractClass(ContainerInterface::class);

        $this->serviceFactory = new ServiceFactory($this->containerMock);
    }

    /**
     * @param string $serviceName
     * @throws \Magento\MagentoCloud\Service\ConfigurationMismatchException
     *
     * @dataProvider createDataProvider
     */
    public function testCreate(string $serviceName)
    {
        $this->containerMock->expects($this->once())
            ->method('create')
            ->with($serviceName)
            ->willReturn($this->getMockForAbstractClass(ServiceInterface::class));

        $this->assertInstanceOf(
            ServiceInterface::class,
            $this->serviceFactory->create($serviceName)
        );
    }

    /**
     * @expectedException \Magento\MagentoCloud\Service\ConfigurationMismatchException
     * @expectedExceptionMessage  Service "wrong-service-name" is not supported
     */
    public function testServiceNotExists()
    {
        $this->containerMock->expects($this->never())
            ->method('create');

        $this->serviceFactory->create('wrong-service-name');
    }

    /**
     * @return array
     */
    public function createDataProvider(): array
    {
        return [
            [ServiceInterface::NAME_REDIS],
            [ServiceInterface::NAME_RABBITMQ],
            [ServiceInterface::NAME_ELASTICSEARCH],
            [ServiceInterface::NAME_DB],
        ];
    }
}
