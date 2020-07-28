<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Config\Factory;

use Magento\MagentoCloud\Config\ConfigMerger;
use Magento\MagentoCloud\Config\Stage\DeployInterface;
use Magento\MagentoCloud\Service\Redis;
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

    const REDIS_BACKEND_CM_CACHE = 'Cm_Cache_Backend_Redis';
    const REDIS_BACKEND_REDIS_CACHE = '\Magento\Framework\Cache\Backend\Redis';
    const REDIS_BACKEND_REMOTE_SYNCHRONIZED_CACHE = '\Magento\Framework\Cache\Backend\RemoteSynchronizedCache';

    const AVAILABLE_REDIS_BACKEND = [
        self::REDIS_BACKEND_CM_CACHE,
        self::REDIS_BACKEND_REDIS_CACHE,
        self::REDIS_BACKEND_REMOTE_SYNCHRONIZED_CACHE
    ];

    /**
     * @var Redis
     */
    private $redis;

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
     * @param Redis $redis
     * @param DeployInterface $stageConfig
     * @param LoggerInterface $logger
     * @param ConfigMerger $configMerger
     */
    public function __construct(
        Redis $redis,
        DeployInterface $stageConfig,
        LoggerInterface $logger,
        ConfigMerger $configMerger
    ) {
        $this->redis = $redis;
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
     * @throws \Magento\MagentoCloud\Config\ConfigException
     */
    public function get(): array
    {
        $envCacheConfiguration = (array)$this->stageConfig->get(DeployInterface::VAR_CACHE_CONFIGURATION);
        $envCacheBackendModel = (string)$this->stageConfig->get(DeployInterface::VAR_CACHE_REDIS_BACKEND);

        if ($this->isCacheConfigurationValid($envCacheConfiguration)
            && !$this->configMerger->isMergeRequired($envCacheConfiguration)
        ) {
            if ($this->stageConfig->get(DeployInterface::VAR_REDIS_USE_SLAVE_CONNECTION)) {
                $this->logger->notice(
                    sprintf(
                        'The variables \'%s\', \'%s\' are ignored as you set your own cache connection in \'%s\'',
                        DeployInterface::VAR_REDIS_USE_SLAVE_CONNECTION,
                        DeployInterface::VAR_CACHE_REDIS_BACKEND,
                        DeployInterface::VAR_CACHE_CONFIGURATION
                    )
                );
            }

            return $this->configMerger->clear($envCacheConfiguration);
        }

        $redisConfig = $this->redis->getConfiguration();

        if (empty($redisConfig)) {
            return [];
        }

        if ($this->isSynchronizedConfigStructure()) {
            $redisCache = $this->getSynchronizedConfigStructure($envCacheBackendModel, $redisConfig);
            $redisCache['backend_options']['remote_backend_options'] = array_merge(
                $redisCache['backend_options']['remote_backend_options'],
                $this->getSlaveConnection($envCacheConfiguration, $redisConfig)
            );
            $finalConfig = [
                'frontend' => [
                    'default' => $redisCache,
                ],
                'type' => [
                    'default' => ['frontend' => 'default'],
                ],
            ];
        } else {
            $redisCache = $this->getUnsyncedConfigStructure($envCacheBackendModel, $redisConfig);
            $slaveConnection = $this->getSlaveConnection($envCacheConfiguration, $redisConfig);
            if ($slaveConnection) {
                $redisCache['frontend_options']['write_control'] = false;
                $redisCache['backend_options'] = array_merge(
                    $redisCache['backend_options'],
                    $slaveConnection
                );
            }
            $finalConfig = [
                'frontend' => [
                    'default' => array_replace_recursive(
                        $redisCache,
                        ['backend_options' => ['database' => self::REDIS_DATABASE_DEFAULT]]
                    ),
                    'page_cache' => array_replace_recursive(
                        $redisCache,
                        ['backend_options' => ['database' => self::REDIS_DATABASE_PAGE_CACHE]]
                    ),
                ]
            ];
        }

        return $this->configMerger->merge($finalConfig, $envCacheConfiguration);
    }

    /**
     * Retrieves Redis read connection data if it exists and variable REDIS_USE_SLAVE_CONNECTION was set as true,
     * also if CACHE_CONFIGURATION is compatible with slave connections.
     * Otherwise retrieves an empty array.
     *
     * @param array $envCacheConfiguration
     * @param array $redisConfig
     * @return array
     * @throws \Magento\MagentoCloud\Config\ConfigException
     */
    private function getSlaveConnection(array $envCacheConfiguration, array $redisConfig): array
    {
        $config = [];
        $redisSlaveConfig = $this->redis->getSlaveConfiguration();
        $slaveHost = $redisSlaveConfig['host'] ?? null;

        if ($this->stageConfig->get(DeployInterface::VAR_REDIS_USE_SLAVE_CONNECTION) && $slaveHost) {
            if ($this->isConfigurationCompatibleWithSlaveConnection($envCacheConfiguration, $redisConfig)) {
                $config['load_from_slave']['server'] = $slaveHost;
                $config['load_from_slave']['port'] = $redisSlaveConfig['port'] ?? '';
                $config['read_timeout'] = 1;
                $config['retry_reads_on_master'] = 1;
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

        return $config;
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
     * Checks that cache configuration was changed in CACHE_CONFIGURATION variable
     * in not compatible way with slave connection.
     *
     * Returns false if server or port was changed in merged configuration otherwise false.
     *
     * @param array $envCacheConfig
     * @param array $redisConfig
     * @return bool
     * @throws \Magento\MagentoCloud\Config\ConfigException
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    private function isConfigurationCompatibleWithSlaveConnection(
        array $envCacheConfig,
        array $redisConfig
    ): bool {
        if ($this->isSynchronizedConfigStructure()) {
            $host = $envCacheConfig['frontend']['default']['backend_options']['remote_backend_options']['server']
                ?? null;

            $port = $envCacheConfig['frontend']['default']['backend_options']['remote_backend_options']['port']
                ?? null;

            if (($host !== null && $host !== $redisConfig['host'])
                || ($port !== null && $port !== $redisConfig['port'])) {
                return false;
            }
        } else {
            foreach (['default', 'page_cache'] as $type) {
                $host = $envCacheConfig['frontend'][$type]['backend_options']['server'] ?? null;
                $port = $envCacheConfig['frontend'][$type]['backend_options']['port'] ?? null;

                if (($host !== null && $host !== $redisConfig['host'])
                    || ($port !== null && $port !== $redisConfig['port'])) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * Returns backend config for unsynced cache implementation.
     *
     * @param string $envCacheBackendModel
     * @param array $redisConfig
     * @return array
     */
    private function getUnsyncedConfigStructure(string $envCacheBackendModel, array $redisConfig): array
    {
        return [
            'backend' => $envCacheBackendModel,
            'backend_options' => [
                'server' => $redisConfig['host'],
                'port' => $redisConfig['port'],
            ]
        ];
    }

    /**
     * Returns backend config for synchronized cache implementation.
     *
     * @param string $envCacheBackendModel
     * @param array $redisConfig
     * @return array
     */
    private function getSynchronizedConfigStructure(string $envCacheBackendModel, array $redisConfig): array
    {
        return [
            'backend' => $envCacheBackendModel,
            'backend_options' => [
                'remote_backend' => addslashes('\Magento\Framework\Cache\Backend\Redis'),
                'remote_backend_options' => [
                    'server' => $redisConfig['host'],
                    'port' => $redisConfig['port'],
                    'database' => self::REDIS_DATABASE_DEFAULT,
                    'persistent' => 0,
                    'password' => '',
                    'compress_data' => '1',
                ],
                'local_backend' => 'Cm_Cache_Backend_File',
                'local_backend_options' => [
                    'cache_dir' => '/dev/shm/'
                ]
            ],
            'frontend_options' => [
                'write_control' => false,
            ]
        ];
    }

    /**
     * Checks that config contains synchronized cache model and need to use synchronized config structure.
     *
     * @return bool
     * @throws \Magento\MagentoCloud\Config\ConfigException
     */
    private function isSynchronizedConfigStructure(): bool
    {
        $model = (string)$this->stageConfig->get(DeployInterface::VAR_CACHE_REDIS_BACKEND);
        return $model === self::REDIS_BACKEND_REMOTE_SYNCHRONIZED_CACHE;
    }
}
