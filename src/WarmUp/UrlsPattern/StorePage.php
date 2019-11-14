<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\WarmUp\UrlsPattern;

use Magento\MagentoCloud\Util\UrlManager;

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
     * Gets urls for store-page entity pattern type
     *
     * @param string $entity
     * @param string $page
     * @param string $storeIds
     * @return array
     */
    public function getUrls(string $entity, string $page, string $storeIds): array
    {
        if ($storeIds === '*') {
            $stores = $this->urlManager->getBaseUrls();
        } else {
            $stores = [];
            foreach (explode('|', $storeIds) as $storeId) {
                if (!empty($storeBaseUrl = $this->urlManager->getStoreBaseUrl($storeId))) {
                    $stores[] = $storeBaseUrl;
                }
            }
        }

        return array_map(function($storeUrl) use ($page) {
            return rtrim($storeUrl, '/') . '/' . ltrim($page, '/');
        }, array_unique($stores));
    }
}
