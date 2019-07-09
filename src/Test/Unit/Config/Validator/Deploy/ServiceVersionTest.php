<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Test\Unit\Config\Validator\Deploy;

use Magento\MagentoCloud\Config\Validator\Deploy\ServiceVersion;
use Magento\MagentoCloud\Service\ServiceMismatchException;
use Magento\MagentoCloud\Service\ServiceInterface;
use Magento\MagentoCloud\Service\ServiceFactory;
use Magento\MagentoCloud\Service\Validator as ServiceVersionValidator;
use Magento\MagentoCloud\Config\Validator\Result\Error;
use Magento\MagentoCloud\Config\Validator\Result\Success;
use Magento\MagentoCloud\Config\Validator\ResultFactory;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

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
     * @var ServiceFactory|MockObject
     */
    private $serviceFactory;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        $this->resultFactoryMock = $this->createConfiguredMock(ResultFactory::class, [
            'success' => $this->createMock(Success::class),
            'error' => $this->createMock(Error::class)
        ]);
        $this->serviceVersionValidatorMock = $this->createMock(ServiceVersionValidator::class);
        $this->serviceFactory = $this->createMock(ServiceFactory::class);

        $this->validator = new ServiceVersion(
            $this->resultFactoryMock,
            $this->serviceVersionValidatorMock,
            $this->serviceFactory
        );
    }

    public function testValidate()
    {
        $service1 = $this->createMock(ServiceInterface::class);
        $service1->expects($this->once())
            ->method('getVersion')
            ->willReturn('0');
        $service2 = $this->createMock(ServiceInterface::class);
        $service2->expects($this->once())
            ->method('getVersion')
            ->willReturn('3.2');
        $service3 = $this->createMock(ServiceInterface::class);
        $service3->expects($this->once())
            ->method('getVersion')
            ->willReturn('10.2');
        $this->serviceFactory->expects($this->exactly(3))
            ->method('create')
            ->willReturnOnConsecutiveCalls($service1, $service2, $service3);
        $this->serviceVersionValidatorMock->expects($this->exactly(2))
            ->method('validateService')
            ->withConsecutive(
                [ServiceInterface::NAME_REDIS, '3.2'],
                [ServiceInterface::NAME_DB, '10.2']
            )
            ->willReturn('');
        $this->resultFactoryMock->expects($this->once())
            ->method('success');

        $this->validator->validate();
    }

    public function testValidateWithErrors()
    {
        $errorMessages = ['error message 1', 'error message 2', 'error message 3'];
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
            ->willReturn('5.7');
        $this->serviceFactory->expects($this->exactly(3))
            ->method('create')
            ->willReturnOnConsecutiveCalls($service1, $service2, $service3);
        $this->serviceVersionValidatorMock->expects($this->exactly(3))
            ->method('validateService')
            ->withConsecutive(
                [ServiceInterface::NAME_RABBITMQ, '1.5'],
                [ServiceInterface::NAME_REDIS, '2.2'],
                [ServiceInterface::NAME_DB, '5.7']
            )
            ->willReturnOnConsecutiveCalls(...$errorMessages);
        $this->resultFactoryMock->expects($this->once())
            ->method('error')
            ->with($this->anything(), implode(PHP_EOL, $errorMessages));

        $this->validator->validate();
    }

    public function testValidateWithException()
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
