<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Test\Unit\Config\Validator\Deploy;

use Magento\MagentoCloud\App\Error as AppError;
use Magento\MagentoCloud\Config\Environment;
use Magento\MagentoCloud\Config\Validator\Deploy\EnvironmentVariables;
use Magento\MagentoCloud\Config\Validator\Result\Error;
use Magento\MagentoCloud\Config\Validator\Result\Success;
use Magento\MagentoCloud\Config\Validator\ResultFactory;
use Magento\MagentoCloud\Config\ValidatorException;
use Magento\MagentoCloud\Package\MagentoVersion;
use Magento\MagentoCloud\Package\UndefinedPackageException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @inheritDoc
 */
class EnvironmentVariablesTest extends TestCase
{
    /**
     * @var EnvironmentVariables
     */
    private $validator;

    /**
     * @var MagentoVersion|MockObject
     */
    private $magentoVersionMock;

    /**
     * @var Environment|MockObject
     */
    private $environmentMock;

    /**
     * @var ResultFactory|MockObject
     */
    private $resultFactoryMock;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        $this->magentoVersionMock = $this->createMock(MagentoVersion::class);
        $this->environmentMock = $this->createMock(Environment::class);
        $this->resultFactoryMock = $this->createMock(ResultFactory::class);

        $this->validator = new EnvironmentVariables(
            $this->environmentMock,
            $this->magentoVersionMock,
            $this->resultFactoryMock
        );
    }

    public function testSuccessVersion(): void
    {
        $this->magentoVersionMock->expects($this->once())
            ->method('satisfies')
            ->with('>= 2.4.0')
            ->willReturn(false);
        $this->resultFactoryMock->expects($this->never())
            ->method('errorByCode');

        $this->assertInstanceOf(
            Success::class,
            $this->validator->validate()
        );
    }

    public function testSuccessNoVariable(): void
    {
        $this->magentoVersionMock->expects($this->once())
            ->method('satisfies')
            ->with('>= 2.4.0')
            ->willReturn(true);
        $this->environmentMock->expects($this->once())
            ->method('getEnv')
            ->with('CONFIG__STORES__DEFAULT__PAYMENT__BRAINTREE__CHANNEL')
            ->willReturn(false);
        $this->resultFactoryMock->expects($this->never())
            ->method('errorByCode');

        $this->assertInstanceOf(
            Success::class,
            $this->validator->validate()
        );
    }

    public function testError(): void
    {
        $this->magentoVersionMock->expects($this->once())
            ->method('satisfies')
            ->with('>= 2.4.0')
            ->willReturn(true);
        $this->environmentMock->expects($this->once())
            ->method('getEnv')
            ->with('CONFIG__STORES__DEFAULT__PAYMENT__BRAINTREE__CHANNEL')
            ->willReturn('Magento_Enterprise_Cloud_BT');
        $this->resultFactoryMock->expects($this->once())
            ->method('errorByCode')
            ->with(AppError::DEPLOY_WRONG_BRAINTREE_VARIABLE);

        $this->assertInstanceOf(
            Error::class,
            $this->validator->validate()
        );
    }

    public function testWithException(): void
    {
        $this->expectException(ValidatorException::class);
        $this->expectExceptionMessage('some error');

        $this->magentoVersionMock->expects($this->once())
            ->method('satisfies')
            ->with('>= 2.4.0')
            ->willThrowException(new UndefinedPackageException('some error'));

        $this->validator->validate();
    }
}
