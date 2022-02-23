<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Test\Unit\Shell;

use Magento\MagentoCloud\App\ContainerInterface;
use Magento\MagentoCloud\Shell\MagentoShell;
use Magento\MagentoCloud\Shell\Shell;
use Magento\MagentoCloud\Shell\ShellFactory;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @inheritdoc
 */
class ShellFactoryTest extends TestCase
{
    /**
     * @var ShellFactory
     */
    private $factory;

    /**
     * @var ContainerInterface|MockObject
     */
    private $containerMock;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->containerMock = $this->getMockForAbstractClass(ContainerInterface::class);

        $this->factory = new ShellFactory(
            $this->containerMock
        );
    }

    public function testCreate()
    {
        $this->containerMock->expects($this->once())
            ->method('create')
            ->with(Shell::class)
            ->willReturn($this->createMock(Shell::class));

        $this->factory->create(ShellFactory::STRATEGY_SHELL);
    }

    public function testCreateMagentoShell()
    {
        $this->containerMock->expects($this->once())
            ->method('create')
            ->with(MagentoShell::class)
            ->willReturn($this->createMock(MagentoShell::class));

        $this->factory->create(ShellFactory::STRATEGY_MAGENTO_SHELL);
    }
}
