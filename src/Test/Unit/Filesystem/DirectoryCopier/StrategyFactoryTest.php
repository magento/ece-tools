<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Test\Unit\Filesystem\DirectoryCopier;

use Magento\MagentoCloud\App\ContainerInterface;
use Magento\MagentoCloud\Filesystem\DirectoryCopier\CopyStrategy;
use Magento\MagentoCloud\Filesystem\DirectoryCopier\CopySubFolderStrategy;
use Magento\MagentoCloud\Filesystem\DirectoryCopier\StrategyFactory;
use Magento\MagentoCloud\Filesystem\DirectoryCopier\StrategyInterface;
use Magento\MagentoCloud\Filesystem\DirectoryCopier\SubSymlinkStrategy;
use Magento\MagentoCloud\Filesystem\DirectoryCopier\SymlinkStrategy;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @inheritdoc
 */
#[AllowMockObjectsWithoutExpectations]
class StrategyFactoryTest extends TestCase
{
    /**
     * @var StrategyFactory
     */
    private $strategyFactory;

    /**
     * @var ContainerInterface|MockObject
     */
    private $containerMock;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        $this->containerMock = $this->createMock(ContainerInterface::class);

        $this->strategyFactory = new StrategyFactory($this->containerMock);
    }

    /**
     * Test create method.
     *
     * @param string $strategy
     * @param string $expectedClass
     * @dataProvider createDataProvider
     * @return void
     */
    #[DataProvider('createDataProvider')]
    public function testCreate(string $strategy, string $expectedClass): void
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

    /**
     * Data provider for create method.
     *
     * @return array
     */
    public static function createDataProvider(): array
    {
        return [
            [
                StrategyInterface::STRATEGY_COPY,
                CopyStrategy::class
            ],
            [
                StrategyInterface::STRATEGY_SYMLINK,
                SymlinkStrategy::class
            ],
            [
                StrategyInterface::STRATEGY_SUB_SYMLINK,
                SubSymlinkStrategy::class
            ],
            [
                StrategyInterface::STRATEGY_COPY_SUB_FOLDERS,
                CopySubFolderStrategy::class
            ],
        ];
    }

    /**
     * Test create method with non-existent strategy.
     *
     * @return void
     */
    public function testCopyFromDirNotExists(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Strategy "not_exists_strategy" does not exist');

        $this->strategyFactory->create('not_exists_strategy');
    }
}
