<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\WarmUp\UrlsPattern;

use Magento\MagentoCloud\WarmUp\UrlsPattern;
use Psr\Log\LoggerInterface;

/**
 * Generates arguments for config:show:urls command.
 */
class CommandArgumentBuilder
{
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param LoggerInterface $logger
     */
    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * Generates arguments for config:show:urls command.
     *
     * @param string $entity
     * @param string $storeIds
     * @return array
     */
    public function generate(string $entity, string $storeIds): array
    {
        $commandArguments = [sprintf('--entity-type=%s', $entity)];
        if ($storeIds && $storeIds !== UrlsPattern::PATTERN_ALL) {
            foreach (explode(UrlsPattern::PATTERN_DELIMITER, $storeIds) as $storeId) {
                $commandArguments[] = sprintf('--store-id=%s', $storeId);
            }
        }

        return $commandArguments;
    }

    /**
     * Generates arguments with product SKUs for config:show:urls command.
     *
     * @param string $entity
     * @param string $storeIds
     * @param string $productSkus
     * @return array
     */
    public function generateWithProductSku(string $entity, string $storeIds, string $productSkus): array
    {
        $commandArguments = $this->generate($entity, $storeIds);

        if ($productSkus === UrlsPattern::PATTERN_ALL) {
            $commandArguments[] = sprintf('--product-limit=%d', UrlsPattern::PRODUCT_LIMIT);
            $this->logger->info(sprintf(
                'In case when product SKUs weren\'t provided product limits set to %d',
                UrlsPattern::PRODUCT_LIMIT
            ));

            return $commandArguments;
        }

        foreach (explode(UrlsPattern::PATTERN_DELIMITER, $productSkus) as $productSku) {
            $commandArguments[] = sprintf('--product-sku=%s', $productSku);
        }

        return $commandArguments;
    }
}
