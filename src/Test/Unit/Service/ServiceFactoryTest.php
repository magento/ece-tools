<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Test\Unit\Service;

use Codeception\PHPUnit\TestCase;
use Magento\MagentoCloud\App\ContainerInterface;
use Magento\MagentoCloud\Service\Database;
use Magento\MagentoCloud\Service\ElasticSearch;
use Magento\MagentoCloud\Service\RabbitMq;
use Magento\MagentoCloud\Service\Redis;
use Magento\MagentoCloud\Service\ServiceFactory;
use Magento\MagentoCloud\Service\ServiceInterface;
use Magento\MagentoCloud\Service\ServiceMismatchException;
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
    public function setUp(): void
    {
        $this->containerMock = $this->getMockForAbstractClass(ContainerInterface::class);

        $this->serviceFactory = new ServiceFactory($this->containerMock);
    }

    /**
     * @param string $serviceName
     * @param string $serviceClass
     * @throws \Magento\MagentoCloud\Service\ServiceMismatchException
     * @dataProvider createDataProvider
     */
    public function testCreate(string $serviceName, string $serviceClass)
    {
        $this->containerMock->expects($this->once())
            ->method('create')
            ->with($serviceClass)
            ->willReturn($this->getMockForAbstractClass(ServiceInterface::class));

        $this->assertInstanceOf(
            ServiceInterface::class,
            $this->serviceFactory->create($serviceName)
        );
    }

    public function testServiceNotExists()
    {
        $this->expectException(ServiceMismatchException::class);
        $this->expectExceptionMessage('Service "wrong-service-name" is not supported');

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
            [ServiceInterface::NAME_REDIS, Redis::class],
            [ServiceInterface::NAME_RABBITMQ, RabbitMq::class],
            [ServiceInterface::NAME_ELASTICSEARCH, ElasticSearch::class],
            [ServiceInterface::NAME_DB_MARIA, Database::class],
        ];
    }
}
