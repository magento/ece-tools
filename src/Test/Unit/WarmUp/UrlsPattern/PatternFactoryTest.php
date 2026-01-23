<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Test\Unit\WarmUp\UrlsPattern;

use Codeception\PHPUnit\TestCase;
use Magento\MagentoCloud\App\ContainerInterface;
use Magento\MagentoCloud\Filesystem\Flag\ConfigurationMismatchException;
use Magento\MagentoCloud\WarmUp\UrlsPattern\CategoryCmsPage;
use Magento\MagentoCloud\WarmUp\UrlsPattern\PatternFactory;
use Magento\MagentoCloud\WarmUp\UrlsPattern\PatternInterface;
use Magento\MagentoCloud\WarmUp\UrlsPattern\Product;
use Magento\MagentoCloud\WarmUp\UrlsPattern\StorePage;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * @inheritDoc
 */
#[AllowMockObjectsWithoutExpectations]
class PatternFactoryTest extends TestCase
{
    /**
     * @var PatternFactory
     */
    private $patternFactory;

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

        $this->patternFactory = new PatternFactory($this->containerMock);
    }

    /**
     * Test create method.
     *
     * @param string $alias
     * @param string $expectedClass
     * @dataProvider createDataProvider
     * @return void
     */
    #[DataProvider('createDataProvider')]
    public function testCreate(string $alias, string $expectedClass): void
    {
        $this->containerMock->expects($this->once())
            ->method('create')
            ->with($expectedClass)
            ->willReturn($this->createMock(PatternInterface::class));

        $this->patternFactory->create($alias);
    }

    /**
     * Data provider for create method.
     *
     * @return array
     */
    public static function createDataProvider(): array
    {
        return [
            ['store-page', StorePage::class],
            ['product', Product::class],
            ['category', CategoryCmsPage::class],
            ['cms-page', CategoryCmsPage::class],
        ];
    }

    /**
     * Test create method with class not exists.
     *
     * @return void
     */
    public function testCreateClassNotExists(): void
    {
        $this->expectException(ConfigurationMismatchException::class);
        $this->expectExceptionMessage('Class wrong_class is not registered');

        $this->patternFactory->create('wrong_class');
    }
}
