<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Config\Factory;

use Magento\MagentoCloud\Config\ConfigMerger;
use Magento\MagentoCloud\Config\Environment;
use Magento\MagentoCloud\Config\Stage\DeployInterface;
use Psr\Log\LoggerInterface;

/**
 * Returns cache configuration.
 */
class Cache
{
    /**
     * Redis database to store default cache data
     */
    const REDIS_DATABASE_DEFAULT = 1;

    /**
     * Redis database to store page cache data
     */
    const REDIS_DATABASE_PAGE_CACHE = 2;

    /**
     * @var Environment
     */
    private $environment;

    /**
     * @var DeployInterface
     */
    private $stageConfig;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var ConfigMerger
     */
    private $configMerger;

    /**
     * @param Environment $environment
     * @param DeployInterface $stageConfig
     * @param LoggerInterface $logger
     * @param ConfigMerger $configMerger
     */
    public function __construct(
        Environment $environment,
        DeployInterface $stageConfig,
        LoggerInterface $logger,
        ConfigMerger $configMerger
    ) {
        $this->environment = $environment;
        $this->stageConfig = $stageConfig;
        $this->logger = $logger;
        $this->configMerger = $configMerger;
    }

    /**
     * Returns session configuration.
     *
     * If cache configuration sets in CACHE_CONFIGURATION variable return it, otherwise checks if exists redis
     * configuration in relationships and if so, makes cache configuration for redis.
     * Returns an empty array in other case.
     *
     * @return array
     */
    public function get(): array
    {
        $envCacheConfiguration = (array)$this->stageConfig->get(DeployInterface::VAR_CACHE_CONFIGURATION);

        if ($this->isCacheConfigurationValid($envCacheConfiguration)
            && !$this->configMerger->isMergeRequired($envCacheConfiguration)
        ) {
            if ($this->stageConfig->get(DeployInterface::VAR_REDIS_USE_SLAVE_CONNECTION)) {
                $this->logger->notice(
                    sprintf(
                        'The variable \'%s\' is ignored as you set your own cache connection in \'%s\'',
                        DeployInterface::VAR_REDIS_USE_SLAVE_CONNECTION,
                        DeployInterface::VAR_CACHE_CONFIGURATION
                    )
                );
            }

            return $this->configMerger->clear($envCacheConfiguration);
        }

        $redisConfig = $this->environment->getRelationship('redis');

        if (empty($redisConfig)) {
            return [];
        }

        $redisCache = [
            'backend' => 'Cm_Cache_Backend_Redis',
            'backend_options' => [
                'server' => $redisConfig[0]['host'],
                'port' => $redisConfig[0]['port'],
            ],
        ];

        $slaveConnectionData = $this->getSlaveConnection();
        if ($slaveConnectionData) {
            if ($this->isConfigurationCompatibleWithSlaveConnection($envCacheConfiguration, $redisConfig)) {
                $redisCache['backend_options']['load_from_slave'] = $slaveConnectionData;
                $this->logger->info('Set Redis slave connection');
            } else {
                $this->logger->notice(
                    sprintf(
                        'The variable \'%s\' is ignored as you\'ve changed cache connection settings in \'%s\'',
                        DeployInterface::VAR_REDIS_USE_SLAVE_CONNECTION,
                        DeployInterface::VAR_CACHE_CONFIGURATION
                    )
                );
            }
        }

        return $this->configMerger->mergeConfigs([
            'frontend' => [
                'default' => array_replace_recursive(
                    $redisCache,
                    ['backend_options' => ['database' => self::REDIS_DATABASE_DEFAULT]]
                ),
                'page_cache' => array_replace_recursive(
                    $redisCache,
                    ['backend_options' => ['database' => self::REDIS_DATABASE_PAGE_CACHE]]
                ),
            ],
        ], $envCacheConfiguration);
    }

    /**
     * Checks that given cache configuration is valid.
     *
     * @param array $cacheConfiguration
     * @return bool
     */
    private function isCacheConfigurationValid(array $cacheConfiguration): bool
    {
        return !$this->configMerger->isEmpty($cacheConfiguration) && !empty($cacheConfiguration['frontend']);
    }

    /**
     * Retrieves Redis read connection data if it exists and variable REDIS_USE_SLAVE_CONNECTION was set as true.
     * Otherwise retrieves an empty array.
     *
     * @return array
     */
    private function getSlaveConnection(): array
    {
        $connectionData = [];
        $redisSlaveConfig = $this->environment->getRelationship('redis-slave');
        $slaveHost = $redisSlaveConfig[0]['host'] ?? null;

        if ($this->stageConfig->get(DeployInterface::VAR_REDIS_USE_SLAVE_CONNECTION) && $slaveHost) {
            $connectionData = [
                'server' => $slaveHost,
                'port' => $redisSlaveConfig[0]['port'] ?? '',
            ];
        }

        return $connectionData;
    }

    /**
     * Checks that cache configuration was changed in CACHE_CONFIGURATION variable
     * in not compatible way with slave connection.
     *
     * Returns false if server or port was changed in merged configuration otherwise false.
     *
     * @param array $envCacheConfig
     * @param array $redisConfig
     * @return bool
     */
    private function isConfigurationCompatibleWithSlaveConnection(
        array $envCacheConfig,
        array $redisConfig
    ): bool {
        foreach (['default', 'page_cache'] as $type) {
            if ((isset($envCacheConfig['frontend'][$type]['backend_options']['server'])
                    && $envCacheConfig['frontend'][$type]['backend_options']['server'] !== $redisConfig[0]['host'])
                || (isset($envCacheConfig['frontend'][$type]['backend_options']['port'])
                    && $envCacheConfig['frontend'][$type]['backend_options']['port'] !== $redisConfig[0]['port'])
            ) {
                return false;
            }
        }

        return true;
    }
}
