<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\WarmUp;

use Magento\MagentoCloud\App\GenericException;
use Magento\MagentoCloud\Shell\ShellException;
use Magento\MagentoCloud\WarmUp\UrlsPattern\PatternFactory;
use Psr\Log\LoggerInterface;

/**
 * Fetch urls from config:show:urls command and filtering them by given pattern.
 */
class UrlsPattern
{
    /**
     * Possible types of warm-up patterns.
     */
    public const ENTITY_CATEGORY = 'category';
    public const ENTITY_CMS_PAGE = 'cms-page';
    public const ENTITY_PRODUCT = 'product';
    public const ENTITY_STORE_PAGE = 'store-page';

    public const PATTERN_DELIMITER = '|';
    public const PATTERN_ALL = '*';

    /**
     * Limit of the product per store when '*' pattern is used for Product entity type.
     */
    public const PRODUCT_LIMIT = 100;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var PatternFactory
     */
    private $patternFactory;

    /**
     * @param LoggerInterface $logger
     * @param PatternFactory $patternFactory
     */
    public function __construct(
        LoggerInterface $logger,
        PatternFactory $patternFactory
    ) {
        $this->logger = $logger;
        $this->patternFactory = $patternFactory;
    }

    /**
     * Fetch urls from config:show:urls command and filtering them by given pattern.
     * Format of the pattern: entity-type:pattern:store-id
     *
     * Example of the warm-up patterns:
     * category:*:*   -  all category pages for all stores
     * category:*:store_code1|store_code2 - all category pages for stores with code store_code1 and store_code2
     * cms-page:|.*contact.*|:*  - cms pages which contains "contact" in the url
     * store-page:/path/to/page:store1|store2 - page /path/to/page for stores with code store1 and store2
     * store-page:/path/to/page:* - page /path/to/page for all stores
     * product:*:* - all product pages for all stores, product page count is limited to 100
     * product:sku1|sku2:store1|store2 - product pages for products with sku sku1 and sku2 and
     *                                   for stores with code store1 and store2
     *
     * @param string $warmUpPattern
     * @return array
     */
    public function get(string $warmUpPattern): array
    {
        try {
            if (!$this->isValid($warmUpPattern)) {
                $this->logger->error(sprintf('Warm-up pattern "%s" isn\'t valid.', $warmUpPattern));
                return [];
            }

            [$entity, $pattern, $storeIds] = explode(':', $warmUpPattern);

            $urlsPattern = $this->patternFactory->create($entity);

            return array_unique($urlsPattern->getUrls($entity, $pattern, $storeIds));
        } catch (ShellException $e) {
            $this->logger->error('Command execution failed: ' . $e->getMessage());
        } catch (GenericException $e) {
            $this->logger->error($e->getMessage());
        }

        return [];
    }

    /**
     * Checks if pattern for warm up is configured properly.
     *
     * @param string $warmUpPattern
     * @return bool
     */
    public function isValid(string $warmUpPattern): bool
    {
        $regex = sprintf(
            '/^(%s|%s|%s|%s):.{1,}:(\w+|\*)/',
            self::ENTITY_CATEGORY,
            self::ENTITY_CMS_PAGE,
            self::ENTITY_PRODUCT,
            self::ENTITY_STORE_PAGE
        );

        return (bool)preg_match($regex, $warmUpPattern);
    }
}
