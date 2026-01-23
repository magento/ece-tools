<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Test\Unit\Config\Validator\Deploy;

use Magento\MagentoCloud\Config\Validator\Deploy\ServiceVersion;
use Magento\MagentoCloud\Config\Validator\Result\Error;
use Magento\MagentoCloud\Config\Validator\Result\Success;
use Magento\MagentoCloud\Config\Validator\ResultFactory;
use Magento\MagentoCloud\Config\ValidatorException;
use Magento\MagentoCloud\Service\Detector\DatabaseType;
use Magento\MagentoCloud\Service\ServiceFactory;
use Magento\MagentoCloud\Service\ServiceInterface;
use Magento\MagentoCloud\Service\ServiceMismatchException;
use Magento\MagentoCloud\Service\Validator as ServiceVersionValidator;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * @inheritdoc
 * @SuppressWarnings("CouplingBetweenObjects")
 */
#[AllowMockObjectsWithoutExpectations]
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
     * @throws     Exception
     */
    protected function setUp(): void
    {
        $this->resultFactoryMock = $this->createConfiguredMock(
            ResultFactory::class,
            [
            'success' => $this->createMock(Success::class),
            'error' => $this->createMock(Error::class)
            ]
        );
        $this->serviceVersionValidatorMock = $this->createMock(ServiceVersionValidator::class);
        $this->serviceFactory = $this->createMock(ServiceFactory::class);
        $this->loggerMock = $this->createMock(LoggerInterface::class);
        $this->databaseTypeMock = $this->createMock(DatabaseType::class);

        $this->validator = new ServiceVersion(
            $this->resultFactoryMock,
            $this->serviceVersionValidatorMock,
            $this->serviceFactory,
            $this->loggerMock,
            $this->databaseTypeMock
        );
    }

    /**
     * @throws ValidatorException
     * @throws Exception
     */
    public function testValidate(): void
    {
        $this->databaseTypeMock->expects($this->once())
            ->method('getServiceName')
            ->willReturn(ServiceInterface::NAME_DB_MARIA);
        $serviceActiveMq = $this->createMock(ServiceInterface::class);
        $serviceActiveMq->expects($this->once())
            ->method('getVersion')
            ->willReturn('2.42');
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
        $serviceValkey = $this->createMock(ServiceInterface::class);
        $serviceValkey->expects($this->once())
            ->method('getVersion')
            ->willReturn('8.0');
        $serviceValkeySession = $this->createMock(ServiceInterface::class);
        $serviceValkeySession->expects($this->once())
            ->method('getVersion')
            ->willReturn('8.0');
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
        $this->serviceFactory->expects($this->exactly(9))
            ->method('create')
            ->willReturnOnConsecutiveCalls(
                $serviceActiveMq,
                $serviceRmq,
                $serviceRedis,
                $serviceRedisSession,
                $serviceValkey,
                $serviceValkeySession,
                $serviceES,
                $serviceOS,
                $serviceMariaDB
            );
        $series = [
            ['Version of service \'activemq-artemis\' is 2.42', []],
            ['Version of service \'rabbitmq\' is not detected', []],
            ['Version of service \'redis\' is 3.2', []],
            ['Version of service \'redis-session\' is 3.2', []],
            ['Version of service \'valkey\' is 8.0', []],
            ['Version of service \'valkey-session\' is 8.0', []],
            ['Version of service \'elasticsearch\' is 7.7', []],
            ['Version of service \'opensearch\' is 1.2', []],
            ['Version of service \'mariadb\' is 10.2', []]
        ];
        $matcher = $this->exactly(9);
        $this->loggerMock->expects($matcher)
            ->method('info')
            // withConsecutive() alternative.
            ->with(
                $this->callback(
                    function ($param) use ($series, $matcher) {
                        $arguments = $series[$this->resolveInvocations($matcher) - 1];  // retrieves arguments
                        $this->assertStringContainsString($arguments[0], $param); // performs assertion on the argument
                        return true;
                    }
                ),
                $this->callback(
                    function ($param) use ($series, $matcher) {
                        $arguments = $series[$this->resolveInvocations($matcher) - 1];  // retrieves arguments
                        $this->assertSame($arguments[1], $param); // performs assertion on the argument
                        return true;
                    }
                ),
            );
        $this->resultFactoryMock->expects($this->once())
            ->method('success');

        $this->validator->validate();
    }

    /**
     * @SuppressWarnings("PHPMD.CyclomaticComplexity")
     * @throws                                         ValidatorException
     * @throws                                         Exception
     */
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
            'error message 6',
            'error message 7',
            'error message 8',
            'error message 9',
        ];
        $service1 = $this->createMock(ServiceInterface::class);
        $service1->expects($this->once())
            ->method('getVersion')
            ->willReturn('2.42');
        $service2 = $this->createMock(ServiceInterface::class);
        $service2->expects($this->once())
            ->method('getVersion')
            ->willReturn('1.5');
        $service3 = $this->createMock(ServiceInterface::class);
        $service3->expects($this->once())
            ->method('getVersion')
            ->willReturn('2.2');
        $service4 = $this->createMock(ServiceInterface::class);
        $service4->expects($this->once())
            ->method('getVersion')
            ->willReturn('2.2');
        $service5 = $this->createMock(ServiceInterface::class);
        $service5->expects($this->once())
            ->method('getVersion')
            ->willReturn('8.0');
        $service6 = $this->createMock(ServiceInterface::class);
        $service6->expects($this->once())
            ->method('getVersion')
            ->willReturn('8.0');
        $service7 = $this->createMock(ServiceInterface::class);
        $service7->expects($this->once())
            ->method('getVersion')
            ->willReturn('7.7');
        $service8 = $this->createMock(ServiceInterface::class);
        $service8->expects($this->once())
            ->method('getVersion')
            ->willReturn('1.2');
        $service9 = $this->createMock(ServiceInterface::class);
        $service9->expects($this->once())
            ->method('getVersion')
            ->willReturn('5.7');
        $this->serviceFactory->expects($this->exactly(9))
            ->method('create')
            ->willReturnOnConsecutiveCalls(
                $service1,
                $service2,
                $service3,
                $service4,
                $service5,
                $service6,
                $service7,
                $service8,
                $service9,
            );
        $this->serviceVersionValidatorMock->expects($this->exactly(9))
            ->method('validateService')
            // withConsecutive() alternative.
            ->willReturnCallback(
                function ($arg1, $arg2) use ($errorMessages) {
                    if ($arg1 == ServiceInterface::NAME_ACTIVEMQ && $arg2 == '2.42') {
                        return $errorMessages[0];
                    } elseif ($arg1 == ServiceInterface::NAME_RABBITMQ && $arg2 == '1.5') {
                        return $errorMessages[1];
                    } elseif ($arg1 == ServiceInterface::NAME_REDIS && $arg2 == '2.2') {
                        return $errorMessages[2];
                    } elseif ($arg1 == ServiceInterface::NAME_REDIS_SESSION && $arg2 == '2.2') {
                        return $errorMessages[3];
                    } elseif ($arg1 == ServiceInterface::NAME_VALKEY && $arg2 == '8.0') {
                        return $errorMessages[4];
                    } elseif ($arg1 == ServiceInterface::NAME_VALKEY_SESSION && $arg2 == '8.0') {
                        return $errorMessages[5];
                    } elseif ($arg1 == ServiceInterface::NAME_ELASTICSEARCH && $arg2 == '7.7') {
                        return $errorMessages[6];
                    } elseif ($arg1 == ServiceInterface::NAME_OPENSEARCH && $arg2 == '1.2') {
                        return $errorMessages[7];
                    } elseif ($arg1 == ServiceInterface::NAME_DB_MYSQL && $arg2 == '5.7') {
                        return $errorMessages[8];
                    }

                    return '';
                }
            );
        $this->resultFactoryMock->expects($this->once())
            ->method('error')
            ->with($this->anything(), implode(PHP_EOL, $errorMessages));

        $this->validator->validate();
    }

    /**
     * @throws ValidatorException
     */
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

    private function resolveInvocations(\PHPUnit\Framework\MockObject\Rule\InvocationOrder $matcher): int
    {
        if (method_exists($matcher, 'numberOfInvocations')) { // PHPUnit 10+ (including PHPUnit 12)
            return $matcher->numberOfInvocations();
        }

        if (method_exists($matcher, 'getInvocationCount')) { // before PHPUnit 10
            return $matcher->getInvocationCount();
        }

        $this->fail('Cannot count the number of invocations.');
    }
}
