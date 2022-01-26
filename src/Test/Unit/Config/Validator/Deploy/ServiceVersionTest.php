<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Test\Unit\Config\Validator\Deploy;

use Magento\MagentoCloud\Config\Validator\Deploy\ServiceVersion;
use Magento\MagentoCloud\Service\Detector\DatabaseType;
use Magento\MagentoCloud\Service\ServiceMismatchException;
use Magento\MagentoCloud\Service\ServiceInterface;
use Magento\MagentoCloud\Service\ServiceFactory;
use Magento\MagentoCloud\Service\Validator as ServiceVersionValidator;
use Magento\MagentoCloud\Config\Validator\Result\Error;
use Magento\MagentoCloud\Config\Validator\Result\Success;
use Magento\MagentoCloud\Config\Validator\ResultFactory;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * @inheritdoc
 */
class ServiceVersionTest extends TestCase
{
    /**
     * @var ServiceVersion
     */
    private $validator;

    /**
     * @var ResultFactory|MockObject
     */
    private $resultFactoryMock;

    /**
     * @var ServiceVersionValidator|MockObject
     */
    private $serviceVersionValidatorMock;

    /**
     * @var LoggerInterface|MockObject
     */
    private $loggerMock;

    /**
     * @var ServiceFactory|MockObject
     */
    private $serviceFactory;

    /**
     * @var DatabaseType|MockObject
     */
    private $databaseTypeMock;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->resultFactoryMock = $this->createConfiguredMock(ResultFactory::class, [
            'success' => $this->createMock(Success::class),
            'error' => $this->createMock(Error::class)
        ]);
        $this->serviceVersionValidatorMock = $this->createMock(ServiceVersionValidator::class);
        $this->serviceFactory = $this->createMock(ServiceFactory::class);
        $this->loggerMock = $this->getMockForAbstractClass(LoggerInterface::class);
        $this->databaseTypeMock = $this->createMock(DatabaseType::class);

        $this->validator = new ServiceVersion(
            $this->resultFactoryMock,
            $this->serviceVersionValidatorMock,
            $this->serviceFactory,
            $this->loggerMock,
            $this->databaseTypeMock
        );
    }

    public function testValidate(): void
    {
        $this->databaseTypeMock->expects($this->once())
            ->method('getServiceName')
            ->willReturn(ServiceInterface::NAME_DB_MARIA);
        $serviceRmq = $this->createMock(ServiceInterface::class);
        $serviceRmq->expects($this->once())
            ->method('getVersion')
            ->willReturn('0');
        $serviceRedis = $this->createMock(ServiceInterface::class);
        $serviceRedis->expects($this->once())
            ->method('getVersion')
            ->willReturn('3.2');
        $serviceRedisSession = $this->createMock(ServiceInterface::class);
        $serviceRedisSession->expects($this->once())
            ->method('getVersion')
            ->willReturn('3.2');
        $serviceES = $this->createMock(ServiceInterface::class);
        $serviceES->expects($this->once())
            ->method('getVersion')
            ->willReturn('7.7');
        $serviceOS = $this->createMock(ServiceInterface::class);
        $serviceOS->expects($this->once())
            ->method('getVersion')
            ->willReturn('1.2');
        $serviceMariaDB = $this->createMock(ServiceInterface::class);
        $serviceMariaDB->expects($this->once())
            ->method('getVersion')
            ->willReturn('10.2');
        $this->serviceFactory->expects($this->exactly(6))
            ->method('create')
            ->willReturnOnConsecutiveCalls(
                $serviceRmq,
                $serviceRedis,
                $serviceRedisSession,
                $serviceES,
                $serviceOS,
                $serviceMariaDB
            );
        $this->loggerMock->expects($this->exactly(6))
            ->method('info')
            ->withConsecutive(
                ['Version of service \'rabbitmq\' is not detected', []],
                ['Version of service \'redis\' is 3.2', []],
                ['Version of service \'redis-session\' is 3.2', []],
                ['Version of service \'elasticsearch\' is 7.7', []],
                ['Version of service \'opensearch\' is 1.2', []],
                ['Version of service \'mariadb\' is 10.2', []]
            );
        $this->serviceVersionValidatorMock->expects($this->exactly(5))
            ->method('validateService')
            ->withConsecutive(
                [ServiceInterface::NAME_REDIS, '3.2'],
                [ServiceInterface::NAME_REDIS_SESSION, '3.2'],
                [ServiceInterface::NAME_ELASTICSEARCH, '7.7'],
                [ServiceInterface::NAME_OPENSEARCH, '1.2'],
                [ServiceInterface::NAME_DB_MARIA, '10.2']
            )
            ->willReturn('');
        $this->resultFactoryMock->expects($this->once())
            ->method('success');

        $this->validator->validate();
    }

    public function testValidateWithErrors(): void
    {
        $this->databaseTypeMock->expects($this->once())
            ->method('getServiceName')
            ->willReturn(ServiceInterface::NAME_DB_MYSQL);
        $errorMessages = [
            'error message 1',
            'error message 2',
            'error message 3',
            'error message 4',
            'error message 5',
            'error message 6'
        ];
        $service1 = $this->createMock(ServiceInterface::class);
        $service1->expects($this->once())
            ->method('getVersion')
            ->willReturn('1.5');
        $service2 = $this->createMock(ServiceInterface::class);
        $service2->expects($this->once())
            ->method('getVersion')
            ->willReturn('2.2');
        $service3 = $this->createMock(ServiceInterface::class);
        $service3->expects($this->once())
            ->method('getVersion')
            ->willReturn('2.2');
        $service4 = $this->createMock(ServiceInterface::class);
        $service4->expects($this->once())
            ->method('getVersion')
            ->willReturn('7.7');
        $service5 = $this->createMock(ServiceInterface::class);
        $service5->expects($this->once())
            ->method('getVersion')
            ->willReturn('1.2');
        $service6 = $this->createMock(ServiceInterface::class);
        $service6->expects($this->once())
            ->method('getVersion')
            ->willReturn('5.7');
        $this->serviceFactory->expects($this->exactly(6))
            ->method('create')
            ->willReturnOnConsecutiveCalls($service1, $service2, $service3, $service4, $service5, $service6);
        $this->serviceVersionValidatorMock->expects($this->exactly(6))
            ->method('validateService')
            ->withConsecutive(
                [ServiceInterface::NAME_RABBITMQ, '1.5'],
                [ServiceInterface::NAME_REDIS, '2.2'],
                [ServiceInterface::NAME_REDIS_SESSION, '2.2'],
                [ServiceInterface::NAME_ELASTICSEARCH, '7.7'],
                [ServiceInterface::NAME_OPENSEARCH, '1.2'],
                [ServiceInterface::NAME_DB_MYSQL, '5.7']
            )
            ->willReturnOnConsecutiveCalls(...$errorMessages);
        $this->resultFactoryMock->expects($this->once())
            ->method('error')
            ->with($this->anything(), implode(PHP_EOL, $errorMessages));

        $this->validator->validate();
    }

    public function testValidateWithException(): void
    {
        $this->serviceFactory->expects($this->any())
            ->method('create')
            ->willThrowException(new ServiceMismatchException('some error'));
        $this->resultFactoryMock->expects($this->once())
            ->method('error')
            ->with('Can\'t validate version of some services: some error');

        $this->validator->validate();
    }
}
