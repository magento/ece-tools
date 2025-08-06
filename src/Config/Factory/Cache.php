<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Config\Factory;

use Magento\MagentoCloud\Config\ConfigException;
use Magento\MagentoCloud\Config\ConfigMerger;
use Magento\MagentoCloud\Config\Stage\DeployInterface;
use Magento\MagentoCloud\Service\Redis;
use Magento\MagentoCloud\Service\Valkey;
use Psr\Log\LoggerInterface;

/**
 * Returns cache configuration.
 */
class Cache
{
     /**
      * Redis database to store default cache data
      */
    public const CACHE_DATABASE_DEFAULT = 1;

    /**
     * Redis database to store page cache data
     */
    public const CACHE_DATABASE_PAGE_CACHE = 2;

    public const REDIS_BACKEND_CM_CACHE = 'Cm_Cache_Backend_Redis';
    public const REDIS_BACKEND_REDIS_CACHE = '\Magento\Framework\Cache\Backend\Redis';

    public const VALKEY_BACKEND_CM_CACHE = 'Cm_Cache_Backend_Redis';
    public const VALKEY_BACKEND_VALKEY_CACHE = '\Magento\Framework\Cache\Backend\Redis';

    public const VALKEY_BACKEND_REMOTE_SYNCHRONIZED_CACHE = '\Magento\Framework\Cache\Backend\RemoteSynchronizedCache';
    public const REDIS_BACKEND_REMOTE_SYNCHRONIZED_CACHE = '\Magento\Framework\Cache\Backend\RemoteSynchronizedCache';

    public const AVAILABLE_REDIS_BACKEND = [
        self::REDIS_BACKEND_CM_CACHE,
        self::REDIS_BACKEND_REDIS_CACHE,
        self::REDIS_BACKEND_REMOTE_SYNCHRONIZED_CACHE
    ];

    public const AVAILABLE_VALKEY_BACKEND = [
        self::REDIS_BACKEND_CM_CACHE,
        self::VALKEY_BACKEND_VALKEY_CACHE,
        self::VALKEY_BACKEND_REMOTE_SYNCHRONIZED_CACHE
    ];

    /**
     * @var Redis
     */
    private Redis $redis;

    /**
     * @var Valkey
     */
    private Valkey $valkey;

    /**
     * @var DeployInterface
     */
    private DeployInterface $stageConfig;

    /**
     * @var LoggerInterface
     */
    private LoggerInterface $logger;

    /**
     * @var ConfigMerger
     */
    private ConfigMerger $configMerger;

    /**
     * @param Valkey          $valkey
     * @param Redis           $redis
     * @param DeployInterface $stageConfig
     * @param LoggerInterface $logger
     * @param ConfigMerger    $configMerger
     */
    public function __construct(
        Redis $redis,
        Valkey $valkey,
        DeployInterface $stageConfig,
        LoggerInterface $logger,
        ConfigMerger $configMerger
    ) {
        $this->redis = $redis;
        $this->valkey = $valkey;
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
     * @SuppressWarnings("CyclomaticComplexity")
     * @return array
     * @throws ConfigException
     */
    public function get(): array
    {
         $envCacheConfiguration = (array)$this->stageConfig->get(DeployInterface::VAR_CACHE_CONFIGURATION);
         $envCacheRadisBackendModel = (string)$this->stageConfig->get(DeployInterface::VAR_CACHE_REDIS_BACKEND);
         $envCacheValkeyBackendModel = (string)$this->stageConfig->get(DeployInterface::VAR_CACHE_VALKEY_BACKEND);

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
            } elseif ($this->stageConfig->get(DeployInterface::VAR_VALKEY_USE_SLAVE_CONNECTION)) {
                $this->logger->notice(
                    sprintf(
                        'The variables \'%s\', \'%s\' are ignored as you set your own cache connection in \'%s\'',
                        DeployInterface::VAR_VALKEY_USE_SLAVE_CONNECTION,
                        DeployInterface::VAR_CACHE_VALKEY_BACKEND,
                        DeployInterface::VAR_CACHE_CONFIGURATION
                    )
                );
            }

            return $this->configMerger->clear($envCacheConfiguration);
        }

        $redisConfig = $this->redis->getConfiguration();

        $valkeyConfig = $this->valkey->getConfiguration();

        if (empty($redisConfig) && empty($valkeyConfig)) {
            return [];
        }

        // Determine backend based on available configuration
        $backendConfig = !empty($redisConfig) ? $redisConfig : $valkeyConfig;
        $cacheBackendModel = !empty($redisConfig) ? $envCacheRadisBackendModel :$envCacheValkeyBackendModel;
        if ($this->isSynchronizedConfigStructure()) {
               $cacheCacheBackend = $this->getSynchronizedConfigStructure($cacheBackendModel, $backendConfig);
                $cacheCacheBackend['backend_options']['remote_backend_options'] = array_merge(
                    $cacheCacheBackend['backend_options']['remote_backend_options'],
                    $this->getSlaveConnection($envCacheConfiguration, $backendConfig)
                );
            $finalConfig = [
                'frontend' => [
                    'default' => $cacheCacheBackend,
                ],
                'type' => [
                    'default' => ['frontend' => 'default'],
                ],
            ];
        } else {
            $cacheCacheBackend = $this->getUnsyncedConfigStructure($cacheBackendModel, $backendConfig);
            $slaveConnection = $this->getSlaveConnection($envCacheConfiguration, $backendConfig);
            if ($slaveConnection) {
                  $cacheCacheBackend['frontend_options']['write_control'] = false;
                $cacheCacheBackend['backend_options'] = array_merge(
                    $cacheCacheBackend['backend_options'],
                    $slaveConnection
                );
            }
            $finalConfig = [
                'frontend' => [
                    'default' => array_replace_recursive(
                        $cacheCacheBackend,
                        ['backend_options' => ['database' => self::CACHE_DATABASE_DEFAULT]]
                    ),
                    'page_cache' => array_replace_recursive(
                        $cacheCacheBackend,
                        ['backend_options' => ['database' => self::CACHE_DATABASE_PAGE_CACHE]]
                    ),
                ]
            ];
        }

        return $this->configMerger->merge($finalConfig, $envCacheConfiguration);
    }

    /**
     * Retrieves Redis or Valkey read connection data if it exists and variable
     * REDIS_USE_SLAVE_CONNECTION or VALKEY_USE_SLAVE_CONNECTION was set as true,
     * also if CACHE_CONFIGURATION is compatible with slave connections.
     * Otherwise, retrieves an empty array.
     *
     * @param  array $envCacheConfiguration
     * @param  array $backendConfig
     * @return array
     * @throws ConfigException
     */
    private function getSlaveConnection(array $envCacheConfiguration, array $backendConfig): array
    {
         $config = [];

        $useRedisSlave = $this->stageConfig->get(DeployInterface::VAR_REDIS_USE_SLAVE_CONNECTION);
        $useValkeySlave = $this->stageConfig->get(DeployInterface::VAR_VALKEY_USE_SLAVE_CONNECTION);

        if ($useRedisSlave) {
            $slaveConfig = $this->redis->getSlaveConfiguration();
            $backendType = 'Redis';
        } elseif ($useValkeySlave) {
            $slaveConfig = $this->valkey->getSlaveConfiguration();
            $backendType = 'Valkey';
        } else {
            return $config; // No slave connection requested
        }
        $slaveHost = $slaveConfig['host'] ?? null;

        if ($slaveHost) {
            if ($this->isConfigurationCompatibleWithSlaveConnection($envCacheConfiguration, $backendConfig)) {
                  $config['load_from_slave']['server'] = $slaveHost;
                  $config['load_from_slave']['port'] = $slaveConfig['port'] ?? '';
                  $config['read_timeout'] = 1;
                  $config['retry_reads_on_master'] = 1;
                if (!empty($slaveConfig['password'])) {
                      $config['load_from_slave']['password'] = $slaveConfig['password'];
                }

                  $this->logger->info(sprintf('Set %s slave connection', $backendType));
            } else {
                $this->logger->notice(
                    sprintf(
                        'The variable \'%s\' is ignored as you\'ve changed cache connection settings in \'%s\'',
                        $useRedisSlave ?
                          DeployInterface::VAR_REDIS_USE_SLAVE_CONNECTION :
                          DeployInterface::VAR_VALKEY_USE_SLAVE_CONNECTION,
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
     * @param  array $cacheConfiguration
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
     * @param                                          array $envCacheConfig
     * @param                                          array $backendConfig
     * @return                                         bool
     * @throws                                         ConfigException
     * @SuppressWarnings("PHPMD.CyclomaticComplexity")
     */
    private function isConfigurationCompatibleWithSlaveConnection(
        array $envCacheConfig,
        array $backendConfig
    ): bool {
        if ($this->isSynchronizedConfigStructure()) {
            $host = $envCacheConfig['frontend']['default']['backend_options']['remote_backend_options']['server']
                ?? null;

            $port = $envCacheConfig['frontend']['default']['backend_options']['remote_backend_options']['port']
                ?? null;

            if (($host !== null && $host !== $backendConfig['host'])
                || ($port !== null && $port !== $backendConfig['port'])
            ) {
                return false;
            }
        } else {
            foreach (['default', 'page_cache'] as $type) {
                $host = $envCacheConfig['frontend'][$type]['backend_options']['server'] ?? null;
                $port = $envCacheConfig['frontend'][$type]['backend_options']['port'] ?? null;

                if (($host !== null && $host !== $backendConfig['host'])
                    || ($port !== null && $port !== $backendConfig['port'])
                ) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * Returns backend config for unsynced cache implementation.
     *
     * @param  string $envCacheBackendModel
     * @param  array  $backendConfig
     * @return array
     */
    private function getUnsyncedConfigStructure(string $envCacheBackendModel, array $backendConfig): array
    {
        $config = [
            'backend' => $envCacheBackendModel,
            'backend_options' => [
                'server' => $backendConfig['host'],
                'port' => $backendConfig['port'],
            ]
        ];

        if (!empty($backendConfig['password'])) {
            $config['backend_options']['password'] = (string)$backendConfig['password'];
        }

        return $config;
    }

    /**
     * Returns backend config for synchronized cache implementation.
     *
     * @param  string $envCacheBackendModel
     * @param  array  $backendConfig
     * @return array
     */
    private function getSynchronizedConfigStructure(string $envCacheBackendModel, array $backendConfig): array
    {

        $config = [
            'backend' => $envCacheBackendModel,
            'backend_options' => [
                'remote_backend' => '\Magento\Framework\Cache\Backend\Redis',
                'remote_backend_options' => [
                    'server' => $backendConfig['host'],
                    'port' => $backendConfig['port'],
                    'database' => self::CACHE_DATABASE_DEFAULT,
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

        if (!empty($backendConfig['password'])) {
            $config['backend_options']['remote_backend_options']['password'] = (string)$backendConfig['password'];
        }

        return $config;
    }

    /**
     * Checks that config contains synchronized cache model and need to use synchronized config structure.
     *
     * @return bool
     * @throws ConfigException
     */
    private function isSynchronizedConfigStructure(): bool
    {
        $redisModel = (string)$this->stageConfig->get(DeployInterface::VAR_CACHE_REDIS_BACKEND);
        $valkeyModel = (string)$this->stageConfig->get(DeployInterface::VAR_CACHE_VALKEY_BACKEND);
        return $redisModel === self::REDIS_BACKEND_REMOTE_SYNCHRONIZED_CACHE ||
        $valkeyModel === self::VALKEY_BACKEND_REMOTE_SYNCHRONIZED_CACHE;
    }

    /**
     * @return array
     */
    public function isValkeyEnabled(): array
    {
        return $this->valkey->getConfiguration();
    }

  /**
   * @return array
   */
    public function isRedisEnabled(): array
    {
        return $this->redis->getConfiguration();
    }
}
