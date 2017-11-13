<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Unit\Filesystem\DirectoryCopier;

use Magento\MagentoCloud\Filesystem\DirectoryCopier\CopyStrategy;
use Magento\MagentoCloud\Filesystem\DirectoryCopier\StrategyFactory;
use Magento\MagentoCloud\Filesystem\DirectoryCopier\StrategyInterface;
use Magento\MagentoCloud\Filesystem\DirectoryCopier\SymlinkStrategy;
use PHPUnit\Framework\TestCase;
use PHPUnit_Framework_MockObject_MockObject as Mock;
use Psr\Container\ContainerInterface;

class StrategyFactoryTest extends TestCase
{
    /**
     * @var StrategyFactory
     */
    private $strategyFactory;

    /**
     * @var ContainerInterface|Mock
     */
    private $containerMock;

    protected function setUp()
    {
        $this->containerMock = $this->getMockForAbstractClass(ContainerInterface::class);

        $this->strategyFactory = new StrategyFactory($this->containerMock);
    }

    /**
     * @dataProvider createDataProvider
     * @param string $strategy
     * @param string $expectedClass
     */
    public function testCreate(string $strategy, string $expectedClass)
    {
        $this->containerMock->expects($this->once())
            ->method('get')
            ->with($expectedClass)
            ->willReturn($this->createMock($expectedClass));

        $this->assertInstanceOf(
            $expectedClass,
            $this->strategyFactory->create($strategy)
        );
    }

    public function createDataProvider()
    {
        return [
            [
                StrategyInterface::STRATEGY_COPY,
                CopyStrategy::class
            ],
            [
                StrategyInterface::STRATEGY_SYMLINK,
                SymlinkStrategy::class
            ]
        ];
    }

    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessage Strategy "not_exists_strategy" not exists
     */
    public function testCopyFromDirNotExists()
    {
        $this->strategyFactory->create('not_exists_strategy');
    }
}
