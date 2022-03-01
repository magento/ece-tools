<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Test\Unit\Config\Validator\Deploy;

use Magento\MagentoCloud\Config\Validator\Deploy\ServiceEol;
use Magento\MagentoCloud\Config\Validator\Result\Error;
use Magento\MagentoCloud\Config\Validator\Result\Success;
use Magento\MagentoCloud\Config\Validator\ResultFactory;
use Magento\MagentoCloud\Config\ValidatorInterface;
use Magento\MagentoCloud\Service\EolValidator;
use Magento\MagentoCloud\Service\ServiceMismatchException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @inheritDoc
 */
class ServiceEolTest extends TestCase
{
    /**
     * @var ServiceEol
     */
    private $validator;

    /**
     * @var ResultFactory|MockObject
     */
    private $resultFactoryMock;

    /**
     * @var EolValidator|MockObject
     */
    private $eolValidatorMock;

    /**
     * @var integer
     */
    private $errorLevel;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        $this->resultFactoryMock = $this->createMock(ResultFactory::class);
        $this->eolValidatorMock = $this->createMock(EolValidator::class);
        $this->errorLevel = ValidatorInterface::LEVEL_NOTICE;

        $this->validator = new ServiceEol(
            $this->resultFactoryMock,
            $this->eolValidatorMock,
            $this->errorLevel
        );
    }

    /**
     * Test valid service.
     */
    public function testValidateWithSuccess()
    {
        $this->eolValidatorMock->expects($this->any())
            ->method('validateServiceEol')
            ->willReturn([]);

        $this->assertInstanceOf(Success::class, $this->validator->validate());
    }

    /**
     * Test with exception.
     */
    public function testValidateWithException()
    {
        $this->eolValidatorMock->expects($this->once())
            ->method('validateServiceEol')
            ->willThrowException(new ServiceMismatchException('error'));

        $this->assertInstanceOf(Error::class, $this->validator->validate());
    }

    /**
     * Test validation.
     */
    public function testValidate()
    {
        $resultArr = [
            ValidatorInterface::LEVEL_WARNING => [
                'warning'
            ],
            ValidatorInterface::LEVEL_NOTICE => [
                'notice'
            ]
        ];

        $this->eolValidatorMock->expects($this->once())
            ->method('validateServiceEol')
            ->willReturn($resultArr);

        $this->assertInstanceOf(Error::class, $this->validator->validate());
    }
}
