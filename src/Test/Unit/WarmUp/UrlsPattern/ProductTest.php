<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Test\Unit\WarmUp\UrlsPattern;

use Codeception\PHPUnit\TestCase;
use Magento\MagentoCloud\App\GenericException;
use Magento\MagentoCloud\WarmUp\UrlsPattern\CommandArgumentBuilder;
use Magento\MagentoCloud\WarmUp\UrlsPattern\ConfigShowUrlCommand;
use Magento\MagentoCloud\WarmUp\UrlsPattern\Product;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * @inheritDoc
 */
class ProductTest extends TestCase
{
    /**
     * @var Product
     */
    private $product;

    /**
     * @var ConfigShowUrlCommand|MockObject
     */
    private $configShowUrlCommandMock;

    /**
     * @var CommandArgumentBuilder|MockObject
     */
    private $argumentBuilderMock;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        $this->configShowUrlCommandMock = $this->createMock(ConfigShowUrlCommand::class);
        $this->argumentBuilderMock = $this->createMock(CommandArgumentBuilder::class);

        $this->product = new Product($this->configShowUrlCommandMock, $this->argumentBuilderMock);
    }

    /**
     * @throws GenericException
     */
    public function testGetUrls()
    {
        $arguments = ['argument1', 'argument2'];

        $this->argumentBuilderMock->expects($this->once())
            ->method('generateWithProductSku')
            ->with('entity', '*', '*')
            ->willReturn($arguments);
        $this->configShowUrlCommandMock->expects($this->once())
            ->method('execute')
            ->with($arguments)
            ->willReturn(['www.example.com']);

        $this->assertEquals(
            ['www.example.com'],
            $this->product->getUrls('entity', '*', '*')
        );
    }
}
