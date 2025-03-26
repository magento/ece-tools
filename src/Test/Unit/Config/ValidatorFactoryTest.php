<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Test\Unit\Config;

use Magento\MagentoCloud\App\Container;
use Magento\MagentoCloud\Config\ValidatorFactory;
use Magento\MagentoCloud\Config\ValidatorInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

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
     * @var ContainerInterface|Container|MockObject
     */
    private $containerMock;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->containerMock = $this->createMock(Container::class);

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
