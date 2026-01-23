<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Test\Unit\WarmUp\UrlsPattern;

use Codeception\PHPUnit\TestCase;
use Magento\MagentoCloud\WarmUp\UrlsPattern\CommandArgumentBuilder;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;

/**
 * @inheritDoc
 */
#[AllowMockObjectsWithoutExpectations]
class CommandArgumentBuilderTest extends TestCase
{
    /**
     * @var CommandArgumentBuilder
     */
    private $argumentBuilder;

    /**
     * @var LoggerInterface|MockObject
     */
    private $loggerMock;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        $this->loggerMock = $this->createMock(LoggerInterface::class);

        $this->argumentBuilder = new CommandArgumentBuilder($this->loggerMock);
    }

    /**
     * Test generate method.
     *
     * @param string $entity
     * @param string $storeIds
     * @param array $expected
     * @dataProvider generateDataProvider
     * @return void
     */
    #[DataProvider('generateDataProvider')]
    public function testGenerate(string $entity, string $storeIds, array $expected)
    {
        $this->assertEquals(
            $expected,
            $this->argumentBuilder->generate($entity, $storeIds)
        );
    }

    /**
     * Data provider for generate method.
     *
     * @return array
     */
    public static function generateDataProvider(): array
    {
        return [
            [
                'category',
                '*',
                [
                    '--entity-type=category',
                ],
            ],
            [
                'cms-page',
                '*',
                [
                    '--entity-type=cms-page',
                ],
            ],
            [
                'category',
                'store_code',
                [
                    '--entity-type=category',
                    '--store-id=store_code',
                ],
            ],
            [
                'category',
                '1|2|3',
                [
                    '--entity-type=category',
                    '--store-id=1',
                    '--store-id=2',
                    '--store-id=3',
                ],
            ],
            [
                'cms-page',
                'Store Code 1|Store Code 2|Store Code 3',
                [
                    '--entity-type=cms-page',
                    '--store-id=Store Code 1',
                    '--store-id=Store Code 2',
                    '--store-id=Store Code 3',
                ],
            ],
        ];
    }

    /**
     * Test generate with product SKUs.
     *
     * @return void
     */
    public function testGenerateWithProductSkus(): void
    {
        $this->loggerMock->expects($this->never())
            ->method('info')
            ->with('In case when product SKUs weren\'t provided product limits set to 100');

        $this->assertEquals(
            [
                '--entity-type=product',
                '--store-id=store_1',
                '--store-id=store_2',
                '--product-sku=sku1',
                '--product-sku=sku2',
            ],
            $this->argumentBuilder->generateWithProductSku('product', 'store_1|store_2', 'sku1|sku2')
        );
    }

    /**
     * Test generate with product SKUs all.
     *
     * @return void
     */
    public function testGenerateWithProductSkusAll(): void
    {
        $this->loggerMock->expects($this->once())
            ->method('info')
            ->with('In case when product SKUs weren\'t provided product limits set to 100');

        $this->assertEquals(
            [
                '--entity-type=product',
                '--store-id=store_1',
                '--store-id=store_2',
                '--product-limit=100'
            ],
            $this->argumentBuilder->generateWithProductSku('product', 'store_1|store_2', '*')
        );
    }
}
