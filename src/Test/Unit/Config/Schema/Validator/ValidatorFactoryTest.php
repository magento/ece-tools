<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Test\Unit\Config\Schema\Validator;

use Magento\MagentoCloud\App\ContainerInterface;
use Magento\MagentoCloud\Config\Schema\Validator\ValidatorFactory;
use Magento\MagentoCloud\Config\Schema\Validator\ValidatorInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @inheritDoc
 */
class ValidatorFactoryTest extends TestCase
{
    /**
     * @var ValidatorFactory|MockObject
     */
    private $validatorFactory;

    /**
     * @var ContainerInterface
     */
    private $containerMock;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        $this->containerMock = $this->getMockForAbstractClass(ContainerInterface::class);

        $this->validatorFactory = new ValidatorFactory(
            $this->containerMock
        );
    }

    public function testCreate(): void
    {
        $this->containerMock->expects($this->once())
            ->method('create')
            ->with('some_class')
            ->willReturn($this->getMockForAbstractClass(ValidatorInterface::class));

        $this->validatorFactory->create('some_class');
    }
}
