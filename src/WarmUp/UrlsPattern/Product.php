<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\WarmUp\UrlsPattern;

/**
 * Processes product pattern type.
 */
class Product implements PatternInterface
{
    /**
     * @var ConfigShowUrlCommand
     */
    private $configShowUrlCommand;

    /**
     * @var CommandArgumentBuilder
     */
    private $commandArgumentBuilder;

    /**
     * @param ConfigShowUrlCommand $configShowUrlCommand
     * @param CommandArgumentBuilder $commandArgumentBuilder
     */
    public function __construct(
        ConfigShowUrlCommand $configShowUrlCommand,
        CommandArgumentBuilder $commandArgumentBuilder
    ) {
        $this->configShowUrlCommand = $configShowUrlCommand;
        $this->commandArgumentBuilder = $commandArgumentBuilder;
    }

    /**
     * @param string $pattern can accept the next value types:
     * - "*" - pages for all products, limited by 100 per store, due to performance reason
     * - "product_sku1" - page for product with product_sku1 SKU
     * - "product_sku1|product_sku2" - pages for products with product_sku1 and product_sku2 SKUs
     *
     * {@inheritDoc}
     */
    public function getUrls(string $entity, string $pattern, string $storeIds): array
    {
        $arguments = $this->commandArgumentBuilder->generateWithProductSku($entity, $storeIds, $pattern);

        return $this->configShowUrlCommand->execute($arguments);
    }
}
