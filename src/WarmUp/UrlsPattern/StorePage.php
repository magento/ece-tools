<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\WarmUp\UrlsPattern;

use Magento\MagentoCloud\Util\UrlManager;
use Magento\MagentoCloud\WarmUp\UrlsPattern;

/**
 * Gets urls for store-page entity pattern type
 */
class StorePage implements PatternInterface
{
    /**
     * @var UrlManager
     */
    private $urlManager;

    /**
     * @param UrlManager $urlManager
     */
    public function __construct(UrlManager $urlManager)
    {
        $this->urlManager = $urlManager;
    }

    /**
     * @param string $pattern - page relative path, for example: "/contact-us"
     *
     * {@inheritDoc}
     */
    public function getUrls(string $entity, string $pattern, string $storeIds): array
    {
        if ($storeIds === '*') {
            $stores = $this->urlManager->getBaseUrls();
        } else {
            $stores = [];
            foreach (explode(UrlsPattern::PATTERN_DELIMITER, $storeIds) as $storeId) {
                if ($storeBaseUrl = $this->urlManager->getStoreBaseUrl($storeId)) {
                    $stores[] = $storeBaseUrl;
                }
            }
        }

        return array_map(function ($storeUrl) use ($pattern) {
            return rtrim($storeUrl, '/') . '/' . ltrim($pattern, '/');
        }, array_unique($stores));
    }
}
