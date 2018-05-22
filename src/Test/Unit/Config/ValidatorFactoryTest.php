<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Unit\Config;

use Magento\MagentoCloud\App\ContainerInterface;
use Magento\MagentoCloud\Config\ValidatorFactory;
use Magento\MagentoCloud\Config\ValidatorInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @inheritdoc
 */
class ValidatorFactoryTest extends TestCase
{
    /**
     * @var ValidatorFactory
     */
    private $validatorFactory;

    /**
     * @var ContainerInterface|MockObject
     */
    private $containerMock;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        $this->containerMock = $this->getMockForAbstractClass(ContainerInterface::class);

        $this->validatorFactory = new ValidatorFactory(
            $this->containerMock
        );
    }

    public function testCreate()
    {
        $validatorMock = $this->getMockForAbstractClass(ValidatorInterface::class);

        $this->containerMock->expects($this->once())
            ->method('create')
            ->with('some_class')
            ->willReturn($validatorMock);

        $this->validatorFactory->create('some_class');
    }
}
