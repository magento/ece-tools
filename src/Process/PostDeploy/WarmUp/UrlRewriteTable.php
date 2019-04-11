<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Process\PostDeploy\WarmUp;

use Magento\MagentoCloud\DB\ConnectionInterface;
use Magento\MagentoCloud\Util\UrlManager;

class UrlRewriteTable
{
    /**
     * Entities from url_rewrites table.
     */
    const ENTITY_CATEGORY = 'category';
    const ENTITY_CMS_PAGE = 'cms-page';

    /**
     * @var ConnectionInterface
     */
    private $connection;

    /**
     * @var UrlManager
     */
    private $urlManager;

    /**
     * @param ConnectionInterface $connection
     * @param UrlManager $urlManager
     */
    public function __construct(ConnectionInterface $connection, UrlManager $urlManager)
    {
        $this->connection = $connection;
        $this->urlManager = $urlManager;
    }

    /**
     * @param string $entity
     * @param string $pattern
     * @param int|null $storeId
     * @return array
     */
    public function getUrls(string $entity, string $pattern, int $storeId = null): array
    {
        $query = 'SELECT u.`request_path`, u.`store_id`, s.`website_id`, s.`group_id` FROM `url_rewrite` u' .
                 ' LEFT JOIN `store` s USING(store_id)' .
                 ' WHERE u.`entity_type` = ?';
        $bindings = [$entity];

        if ($storeId) {
            $query .= ' AND u.`store_id` = ?';
            $bindings[] = $storeId;
        }

        if ($pattern !== '*') {
            $query .= ' AND u.`request_path` LIKE ?';
            $bindings[] = str_replace('*', '%', $pattern);
        }

        $urlRewrites = $this->connection->select($query, $bindings);

        $groupedBaseUrls = $this->urlManager->getGroupedBaseUrls();

        return $this->combineUrls($urlRewrites, $groupedBaseUrls);
    }

    /**
     * Matches and combines urls with site base urls.
     * Base url priority site > website > default.
     *
     * @param array $urlRewrites
     * @param array $groupedBaseUrls
     * @return array
     */
    private function combineUrls(array $urlRewrites, array $groupedBaseUrls): array
    {
        $urls = [];
        $defaultBaseUrl = $this->urlManager->getBaseUrl();

        foreach ($urlRewrites as $urlInfo) {
            if (isset($groupedBaseUrls[UrlManager::SCOPE_STORE][$urlInfo['store_id']])) {
                $baseUrlInfo = $groupedBaseUrls[UrlManager::SCOPE_STORE][$urlInfo['scope_id']];
            } elseif (isset($groupedBaseUrls[UrlManager::SCOPE_WEBSITE][$urlInfo['website_id']])) {
                $baseUrlInfo = $groupedBaseUrls[UrlManager::SCOPE_WEBSITE][$urlInfo['website_id']];
            } else {
                $baseUrlInfo = $groupedBaseUrls[UrlManager::SCOPE_DEFAULT] ?? ['value' => $defaultBaseUrl];
            }

            $urls[] = $baseUrlInfo['value'] . $urlInfo['request_path'];
        }

        return $urls;
    }
}
