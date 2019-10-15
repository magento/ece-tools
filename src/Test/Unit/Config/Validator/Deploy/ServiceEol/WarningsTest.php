<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Test\Unit\Config\Validator\Deploy\ServiceEol;

use Magento\MagentoCloud\App\GenericException;
use Magento\MagentoCloud\Config\Validator\Deploy\ServiceEol\Warnings;
use Magento\MagentoCloud\Config\Validator\Result\Error;
use Magento\MagentoCloud\Config\Validator\Result\Success;
use Magento\MagentoCloud\Config\Validator\ResultFactory;
use Magento\MagentoCloud\Config\ValidatorInterface;
use Magento\MagentoCloud\Service\EolValidator;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @inheritdoc
 */
class WarningsTest extends TestCase
{
    /**
     * @var Warnings
     */
    private $validator;

    /**
     * @var ResultFactory|MockObject
     */
    private $resultFactoryMock;

    /**
     * @var ResultFactory|MockObject
     */
    private $eolValidatorMock;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        $this->resultFactoryMock = $this->createMock(ResultFactory::class);
        $this->eolValidatorMock = $this->createMock(EolValidator::class);

        $this->validator = new Warnings(
            $this->resultFactoryMock,
            $this->eolValidatorMock
        );
    }

    /**
     * @throws \Exception
     */
    public function testValidate()
    {
        $this->eolValidatorMock->expects($this->once())
            ->method('validateServiceEol')
            ->with(ValidatorInterface::LEVEL_WARNING)
            ->willReturn(['warning']);

        $this->assertInstanceOf(Error::class, $this->validator->validate());
    }

    /**
     * @throws \Exception
     */
    public function testValidateWithSuccess()
    {
        $this->eolValidatorMock->expects($this->once())
            ->method('validateServiceEol')
            ->with(ValidatorInterface::LEVEL_WARNING)
            ->willReturn([]);

        $this->assertInstanceOf(Success::class, $this->validator->validate());
    }

    /**
     * @throws \Exception
     */
    public function testValidateWithException()
    {
        $this->eolValidatorMock->expects($this->once())
            ->method('validateServiceEol')
            ->willThrowException(new GenericException('error'));

        $this->validator->validate();
    }
}
