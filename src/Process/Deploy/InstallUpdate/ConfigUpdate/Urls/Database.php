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
 * Updates the base_url configuration in the `core_config_data` table
 *
 * {@inheritdoc}
 */
class Database implements ProcessInterface
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
        $this->logger->info('Updating secure and unsecure URLs in core_config_data table.');

        $baseUrls = $this->getBaseUrls();

        foreach ($this->urlManager->getUrls() as $typeUrl => $actualUrl) {
            if (empty($actualUrl['']) || empty($baseUrls[$typeUrl])) {
                continue;
            }
            $baseHost = parse_url($baseUrls[$typeUrl], PHP_URL_HOST);
            $actualHost = parse_url($actualUrl[''], PHP_URL_HOST);

            if ($baseHost === $actualHost) {
                continue;
            }

            $changedRowsCount = $this->updateUrl($baseHost, $actualHost);

            if (!$changedRowsCount) {
                continue;
            }
            $this->logger->info(sprintf('Host was replaced: [%s] => [%s]', $baseHost, $actualHost));
        }
    }

    /**
     * Returns the base_url configuration from `core_config_data` table.
     *
     * ```php
     * array(
     *     'secure' => 'https://example.com',
     *     'unsecure' => 'http://example.com',
     * )
     * ```
     * @return array
     */
    private function getBaseUrls(): array
    {
        $configBaseUrls = $this->connection->select(
            sprintf(
                'SELECT `value`, `path` FROM `%s` WHERE (`path`=? OR `path`= ?) AND `scope_id` = ?',
                $this->connection->getTableName('core_config_data')
            ),
            [
                'web/unsecure/base_url',
                'web/secure/base_url',
                0,
            ]
        );
        $result = [];
        foreach ($configBaseUrls as $configBaseUrl) {
            $key = $configBaseUrl['path'] === 'web/secure/base_url' ? 'secure' : 'unsecure';
            $result[$key] = $configBaseUrl['value'];
        }

        return $result;
    }

    /**
     * Updates the base_url configuration in the `core_config_data` table.
     *
     * @param string $baseHost
     * @param string $actualHost
     *
     * @return int Returns the number of updated URLs
     */
    private function updateUrl(string $baseHost, string $actualHost): int
    {
        return $this->connection->affectingQuery(
            sprintf(
                'UPDATE `%s` SET `value` = REPLACE(`value`, ?, ?) WHERE `value` LIKE ?',
                $this->connection->getTableName('core_config_data')
            ),
            [
                $baseHost,
                $actualHost,
                '%' . $baseHost . '%',
            ]
        );
    }
}
