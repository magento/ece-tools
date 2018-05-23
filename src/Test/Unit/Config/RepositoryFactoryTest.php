<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Unit\Config;

use Illuminate\Config\Repository;
use Magento\MagentoCloud\App\ContainerInterface;
use Magento\MagentoCloud\Config\RepositoryFactory;
use PHPUnit\Framework\TestCase;
use PHPUnit_Framework_MockObject_MockObject as Mock;

/**
 * @inheritdoc
 */
class RepositoryFactoryTest extends TestCase
{
    /**
     * @var RepositoryFactory
     */
    private $factory;

    /**
     * @var ContainerInterface|Mock
     */
    private $containerMock;

    /**
     * @var Repository|Mock
     */
    private $repositoryMock;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        $this->containerMock = $this->getMockForAbstractClass(ContainerInterface::class);
        $this->repositoryMock = $this->createMock(Repository::class);

        $this->factory = new RepositoryFactory(
            $this->containerMock
        );
    }

    public function testCreate()
    {
        $items = [
            'some_item' => 1,
        ];

        $this->containerMock->expects($this->once())
            ->method('create')
            ->with(Repository::class, ['items' => $items])
            ->willReturn($this->repositoryMock);

        $this->assertSame(
            $this->repositoryMock,
            $this->factory->create($items)
        );
    }
}
