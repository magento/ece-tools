<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Process\Deploy\InstallUpdate\ConfigUpdate;

use Magento\MagentoCloud\Config\Environment;
use Magento\MagentoCloud\DB\ConnectionInterface;
use Magento\MagentoCloud\Process\ProcessInterface;
use Magento\MagentoCloud\Util\UrlManager;
use Psr\Log\LoggerInterface;

/**
 * @inheritdoc
 */
class Urls implements ProcessInterface
{
    /**
     * @var Environment
     */
    private $environment;

    /**
     * @var ConnectionInterface
     */
    private $connection;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var UrlManager
     */
    private $urlManager;

    /**
     * @param Environment $environment
     * @param ConnectionInterface $connection
     * @param LoggerInterface $logger
     * @param UrlManager $urlManager
     */
    public function __construct(
        Environment $environment,
        ConnectionInterface $connection,
        LoggerInterface $logger,
        UrlManager $urlManager
    ) {
        $this->environment = $environment;
        $this->connection = $connection;
        $this->logger = $logger;
        $this->urlManager = $urlManager;
    }

    /**
     * @inheritdoc
     */
    public function execute()
    {
        if (!$this->environment->isUpdateUrlsEnabled()) {
            $this->logger->info('Skipping URL updates');

            return;
        }

        $this->logger->info('Updating secure and unsecure URLs');

        foreach ($this->urlManager->getUrls() as $urlType => $urls) {
            foreach ($urls as $route => $url) {
                $this->update($urlType, $url, $route);
            }
        }
    }

    /**
     * Updates core config with new URLs.
     *
     * @param string $urlType
     * @param string $url
     * @param string $route
     */
    private function update(string $urlType, string $url, string $route)
    {
        $prefix = 'unsecure' === $urlType ? UrlManager::PREFIX_UNSECURE : UrlManager::PREFIX_SECURE;
        if (!strlen($route)) {
            // @codingStandardsIgnoreStart
            $this->connection->affectingQuery(
                "UPDATE `core_config_data` SET `value` = '$url' WHERE `path` = 'web/$urlType/base_url' AND `scope_id` = '0'"
            );

            // @codingStandardsIgnoreEnd
            return;
        }
        $likeKey = $prefix . $route . '%';
        $likeKeyParsed = $prefix . str_replace('.', '---', $route) . '%';
        // @codingStandardsIgnoreStart
        $this->connection->affectingQuery(
            "UPDATE `core_config_data` SET `value` = '$url' WHERE `path` = 'web/$urlType/base_url' AND (`value` LIKE '$likeKey' OR `value` LIKE '$likeKeyParsed')"
        );
        // @codingStandardsIgnoreEnd
    }
}
