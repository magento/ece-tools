<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Process\Deploy\InstallUpdate\ConfigUpdate\Urls;

use Magento\MagentoCloud\Config\Environment;
use Magento\MagentoCloud\DB\ConnectionInterface;
use Magento\MagentoCloud\Process\ProcessInterface;
use Magento\MagentoCloud\Util\UrlManager;
use Psr\Log\LoggerInterface;

/**
 * @inheritdoc
 */
class Db implements ProcessInterface
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
        $this->logger->info('Updating secure and unsecure URLs in database.core_config_data table.');

        $configBaseUrls = $this->getConfigBaseUrls();

        foreach ($this->urlManager->getUrls() as $typeUrl => $actualUrl) {
            if (empty($actualUrl['']) || empty($configBaseUrls[$typeUrl])) {
                continue;
            }
            $baseUrlHost = parse_url($configBaseUrls[$typeUrl])['host'];
            $actualUrlHost = parse_url($actualUrl[''])['host'];

            if ($baseUrlHost === $actualUrlHost) {
                continue;
            }

            $changedRowsCount = $this->updateUrl($baseUrlHost, $actualUrlHost);

            if (0 === $changedRowsCount) {
                continue;
            }
            $this->logger->info(sprintf('Replace host: [%s] => [%s]', $baseUrlHost, $actualUrlHost));
        }
    }

    /**
     * Returns the base_url configuration from `core_config_data` table.
     *
     * ```
     * array(
     *     'secure' => 'https://example.com',
     *     'unsecure' => 'http://example.com',
     * )
     * ```
     * @return array
     */
    private function getConfigBaseUrls()
    {
        $configBaseUrls = $this->connection->select(
            'SELECT `value`, `path` FROM `core_config_data` WHERE (`path`=? OR `path`= ?) AND `scope_id` = ?',
            [
                'web/unsecure/base_url',
                'web/secure/base_url',
                0
            ]
        );
        $result = [];
        foreach ($configBaseUrls as $configBaseUrl) {
            $key = $configBaseUrl['path'] == 'web/secure/base_url' ? 'secure' : 'unsecure';
            $result[$key] = $configBaseUrl['value'];
        }

        return $result;
    }

    /**
     * Returns the number of updated URLs
     *
     * @param $baseHost
     * @param $actualHost
     *
     * @return int
     */
    private function updateUrl($baseHost, $actualHost)
    {
        return $this->connection->affectingQuery(
            'UPDATE `core_config_data` SET `value` = REPLACE(`value`, ?, ?) WHERE `value` LIKE ?',
            [
                $baseHost,
                $actualHost,
                '%' . $baseHost . '%'
            ]
        );
    }
}
