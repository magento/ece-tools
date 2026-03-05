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
use Magento\MagentoCloud\Service\Valkey;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * @inheritdoc
 */
#[AllowMockObjectsWithoutExpectations]
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
        $this->containerMock = $this->createMock(ContainerInterface::class);

        $this->serviceFactory = new ServiceFactory($this->containerMock);
    }

    /**
     * Test create method.
     *
     * @param string $serviceName
     * @param string $serviceClass
     * @dataProvider createDataProvider
     * @return void
     * @throws ServiceMismatchException
     */
    #[DataProvider('createDataProvider')]
    public function testCreate(string $serviceName, string $serviceClass): void
    {
        $this->containerMock->expects($this->once())
            ->method('create')
            ->with($serviceClass)
            ->willReturn($this->createMock(ServiceInterface::class));

        $this->assertInstanceOf(
            ServiceInterface::class,
            $this->serviceFactory->create($serviceName)
        );
    }

    /**
     * Test service not exists.
     *
     * @return void
     * @throws ServiceMismatchException
     */
    public function testServiceNotExists(): void
    {
        $this->expectException(ServiceMismatchException::class);
        $this->expectExceptionMessage('Service "wrong-service-name" is not supported');

        $this->containerMock->expects($this->never())
            ->method('create');

        $this->serviceFactory->create('wrong-service-name');
    }

    /**
     * Data provider for create method.
     *
     * @return array
     */
    public static function createDataProvider(): array
    {
        return [
            [
                ServiceInterface::NAME_REDIS,
                Redis::class
            ],
            [
                ServiceInterface::NAME_VALKEY,
                Valkey::class
            ],
            [
                ServiceInterface::NAME_RABBITMQ,
                RabbitMq::class
            ],
            [
                ServiceInterface::NAME_ELASTICSEARCH,
                ElasticSearch::class
            ],
            [
                ServiceInterface::NAME_DB_MARIA,
                Database::class
            ],
        ];
    }
}
