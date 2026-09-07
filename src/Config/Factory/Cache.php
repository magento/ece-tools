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
    public const VALKEY_BACKEND_REDIS_CACHE = '\Magento\Framework\Cache\Backend\Redis';
    public const VALKEY_BACKEND_VALKEY_CACHE = '\Magento\Framework\Cache\Backend\Valkey';

    public const VALKEY_BACKEND_REMOTE_SYNCHRONIZED_CACHE = '\Magento\Framework\Cache\Backend\RemoteSynchronizedCache';
    public const REDIS_BACKEND_REMOTE_SYNCHRONIZED_CACHE = '\Magento\Framework\Cache\Backend\RemoteSynchronizedCache';

    public const VALKEY_BACKEND_SYMFONY_L2 = 'symfony_l2';
    public const REDIS_BACKEND_SYMFONY_L2 = 'symfony_l2';

    /**
     * Short aliases accepted in place of the full backend class name for CACHE_REDIS_BACKEND/
     * CACHE_VALKEY_BACKEND, resolved to REDIS_BACKEND_REDIS_CACHE/VALKEY_BACKEND_VALKEY_CACHE
     * wherever the configured backend model is read.
     */
    public const REDIS_BACKEND_ALIAS = 'redis';
    public const VALKEY_BACKEND_ALIAS = 'valkey';

    public const AVAILABLE_REDIS_BACKEND = [
        self::REDIS_BACKEND_CM_CACHE,
        self::REDIS_BACKEND_REDIS_CACHE,
        self::REDIS_BACKEND_REMOTE_SYNCHRONIZED_CACHE,
        self::REDIS_BACKEND_SYMFONY_L2,
    ];

    public const AVAILABLE_VALKEY_BACKEND = [
        self::REDIS_BACKEND_CM_CACHE,
        self::VALKEY_BACKEND_REDIS_CACHE,
        self::VALKEY_BACKEND_VALKEY_CACHE,
        self::VALKEY_BACKEND_REMOTE_SYNCHRONIZED_CACHE,
        self::VALKEY_BACKEND_SYMFONY_L2,
    ];

    /**
     * Frontend names used by the symfony_l2 cache backend structure.
     */
    public const SYMFONY_L2_FRONTENDS = ['default', 'stale_cache_enabled'];

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
         $envCacheConfiguration      = (array)$this->stageConfig->get(DeployInterface::VAR_CACHE_CONFIGURATION);
         $envCacheRedisBackendModel  = $this->getRedisBackendModel();
         $envCacheValkeyBackendModel = $this->getValkeyBackendModel();

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

        $redisConfig  = $this->redis->getConfiguration();
        $valkeyConfig = $this->valkey->getConfiguration();
        if (empty($redisConfig) && empty($valkeyConfig)) {
            return [];
        }
        // Determine backend based on available configuration
        $backendConfig     = !empty($redisConfig) ? $redisConfig : $valkeyConfig;
        $cacheBackendModel = !empty($redisConfig) ? $envCacheRedisBackendModel : $envCacheValkeyBackendModel;
        $activeBackend     = !empty($redisConfig) ? 'redis' : 'valkey';

        $slaveConnectionBackend = $this->resolveSlaveConnectionBackend(
            $envCacheRedisBackendModel,
            $envCacheValkeyBackendModel,
            $activeBackend
        );
        if ($this->isSymfonyL2Structure()) {
            $finalConfig = $this->getSymfonyL2ConfigStructure($backendConfig, $activeBackend);
            $finalConfig = $this->applySymfonyL2SlaveConnection(
                $finalConfig,
                $envCacheConfiguration,
                $backendConfig,
                $slaveConnectionBackend
            );
        } elseif ($this->isSynchronizedConfigStructure()) {
            $finalConfig = $this->getSynchronizedFinalConfig(
                $cacheBackendModel,
                $backendConfig,
                $envCacheConfiguration,
                $slaveConnectionBackend
            );
        } else {
            $cacheCacheBackend = $this->getUnsyncedConfigStructure($cacheBackendModel, $backendConfig);
            $slaveConnection = $this->getSlaveConnection(
                $envCacheConfiguration,
                $backendConfig,
                $slaveConnectionBackend
            );
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
     * Determines which backend the *_USE_SLAVE_CONNECTION flags should be matched against.
     *
     * This is NOT necessarily $activeBackend: a Valkey service migrated from Redis is conventionally
     * still exposed under the relationship literally named 'redis' (see Redis::RELATIONSHIP_KEY), so
     * $activeBackend would resolve to 'redis' even though the merchant configured
     * CACHE_VALKEY_BACKEND/VALKEY_BACKEND and set VALKEY_USE_SLAVE_CONNECTION. The explicitly
     * configured backend model takes precedence; $activeBackend is used only as a fallback when
     * neither *_BACKEND variable is explicitly set.
     *
     * REDIS_BACKEND and VALKEY_BACKEND both default (in config/schema.yaml) to
     * self::REDIS_BACKEND_CM_CACHE/self::VALKEY_BACKEND_CM_CACHE ('Cm_Cache_Backend_Redis'), and that
     * default is merged in unconditionally by Deploy\MergedConfig - so the raw value is never ''
     * even when the merchant never set it. Comparing against '' alone can therefore never detect
     * "not explicitly set"; the value must also be compared against its own default.
     *
     * @param  string $envCacheRedisBackendModel
     * @param  string $envCacheValkeyBackendModel
     * @param  string $activeBackend 'redis' or 'valkey', matching the backend $backendConfig came from
     * @return string 'redis' or 'valkey'
     */
    private function resolveSlaveConnectionBackend(
        string $envCacheRedisBackendModel,
        string $envCacheValkeyBackendModel,
        string $activeBackend
    ): string {
        if ($envCacheRedisBackendModel !== '' && $envCacheRedisBackendModel !== self::REDIS_BACKEND_CM_CACHE) {
            return 'redis';
        }

        if ($envCacheValkeyBackendModel !== '' && $envCacheValkeyBackendModel !== self::VALKEY_BACKEND_CM_CACHE) {
            return 'valkey';
        }

        return $activeBackend;
    }

    /**
     * Resolves the slave/replica relationship data for $activeBackend, but only when the
     * *_USE_SLAVE_CONNECTION flag matching that backend is enabled.
     *
     * The flag is gated on $activeBackend (rather than checked in isolation) so that, if both Redis
     * and Valkey relationships happen to exist at once, a slave flag left set for the backend that
     * isn't actually active can't attach a replica from one backend to the master of the other.
     *
     * @param  string $activeBackend 'redis' or 'valkey' - the backend selected via the
     *                                CACHE_REDIS_BACKEND/CACHE_VALKEY_BACKEND config (not necessarily
     *                                which relationship supplied $backendConfig: a Valkey service
     *                                migrated from Redis is conventionally still exposed under the
     *                                relationship literally named 'redis')
     * @return array{0: array, 1: string, 2: string}|null [$slaveConfig, $backendType, $flagVariable]
     */
    private function resolveActiveSlaveConfig(string $activeBackend): ?array
    {
        if ($activeBackend === 'redis'
            && $this->stageConfig->get(DeployInterface::VAR_REDIS_USE_SLAVE_CONNECTION)
        ) {
            return [
                $this->redis->getSlaveConfiguration(),
                'Redis',
                DeployInterface::VAR_REDIS_USE_SLAVE_CONNECTION,
            ];
        }

        if ($activeBackend === 'valkey'
            && $this->stageConfig->get(DeployInterface::VAR_VALKEY_USE_SLAVE_CONNECTION)
        ) {
            return [
                $this->valkey->getSlaveConfiguration(),
                'Valkey',
                DeployInterface::VAR_VALKEY_USE_SLAVE_CONNECTION,
            ];
        }

        return null;
    }

    /**
     * Retrieves Redis or Valkey read connection data if it exists and the *_USE_SLAVE_CONNECTION
     * variable matching $activeBackend was set as true, also if CACHE_CONFIGURATION is compatible
     * with slave connections. Otherwise, retrieves an empty array.
     *
     * @param  array  $envCacheConfiguration
     * @param  array  $backendConfig
     * @param  string $activeBackend 'redis' or 'valkey' - the backend selected via the
     *                                CACHE_REDIS_BACKEND/CACHE_VALKEY_BACKEND config
     * @return array
     * @throws ConfigException
     */
    private function getSlaveConnection(
        array $envCacheConfiguration,
        array $backendConfig,
        string $activeBackend
    ): array {
        $config = [];

        $resolved = $this->resolveActiveSlaveConfig($activeBackend);
        if ($resolved === null) {
            return $config; // No slave connection requested for the active backend
        }
        [$slaveConfig, $backendType, $flagVariable] = $resolved;
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
                        $flagVariable,
                        DeployInterface::VAR_CACHE_CONFIGURATION
                    )
                );
            }
        }

        return $config;
    }

    /**
     * Applies the slave/replica connection to whichever symfony_l2 frontends are individually
     * compatible with it, skipping only the frontends whose CACHE_CONFIGURATION override points at a
     * different host/port (rather than disabling slave routing for both frontends on a single mismatch).
     *
     * @param  array  $finalConfig
     * @param  array  $envCacheConfiguration
     * @param  array  $backendConfig
     * @param  string $activeBackend 'redis' or 'valkey' - the backend selected via the
     *                                CACHE_REDIS_BACKEND/CACHE_VALKEY_BACKEND config
     * @return array
     * @throws ConfigException
     */
    private function applySymfonyL2SlaveConnection(
        array $finalConfig,
        array $envCacheConfiguration,
        array $backendConfig,
        string $activeBackend
    ): array {
        $resolved = $this->resolveActiveSlaveConfig($activeBackend);
        if ($resolved === null) {
            return $finalConfig;
        }
        [$slaveConfig, $backendType, $flagVariable] = $resolved;
        $slaveHost = $slaveConfig['host'] ?? null;
        if (!$slaveHost) {
            return $finalConfig;
        }

        $slaveConnectionData = [
            'load_from_slave' => ['server' => $slaveHost, 'port' => $slaveConfig['port'] ?? ''],
            'read_timeout' => 1,
            'retry_reads_on_master' => 1,
        ];
        if (!empty($slaveConfig['password'])) {
            $slaveConnectionData['load_from_slave']['password'] = $slaveConfig['password'];
        }

        $applied = false;
        foreach (self::SYMFONY_L2_FRONTENDS as $frontendName) {
            $isCompatible = $this->isConfigurationCompatibleWithSlaveConnection(
                $envCacheConfiguration,
                $backendConfig,
                $frontendName
            );
            if ($isCompatible) {
                $finalConfig['frontend'][$frontendName]['backend_options']['remote_backend_options'] = array_merge(
                    $finalConfig['frontend'][$frontendName]['backend_options']['remote_backend_options'],
                    $slaveConnectionData
                );
                $applied = true;
            } else {
                $this->logger->notice(
                    sprintf(
                        'The variable \'%s\' is ignored as you\'ve changed cache connection settings in \'%s\'',
                        $flagVariable,
                        DeployInterface::VAR_CACHE_CONFIGURATION
                    )
                );
            }
        }

        if ($applied) {
            $this->logger->info(sprintf('Set %s slave connection', $backendType));
        }

        return $finalConfig;
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
     * For the symfony_l2 structure, compatibility is checked per frontend: passing $frontendName
     * restricts the check to that frontend so a mismatch on one (e.g. 'stale_cache_enabled') doesn't
     * disable slave routing for an otherwise-compatible 'default' frontend. Omitting it checks all
     * symfony_l2 frontends together.
     *
     * @param                                          array       $envCacheConfig
     * @param                                          array       $backendConfig
     * @param                                          string|null $frontendName
     * @return                                         bool
     * @throws                                         ConfigException
     * @SuppressWarnings("PHPMD.CyclomaticComplexity")
     */
    private function isConfigurationCompatibleWithSlaveConnection(
        array $envCacheConfig,
        array $backendConfig,
        ?string $frontendName = null
    ): bool {
        if ($this->isSymfonyL2Structure()) {
            $frontendNames = $frontendName !== null ? [$frontendName] : self::SYMFONY_L2_FRONTENDS;
            foreach ($frontendNames as $name) {
                $remoteBackendOptions =
                    $envCacheConfig['frontend'][$name]['backend_options']['remote_backend_options'] ?? [];
                $host = $remoteBackendOptions['server'] ?? null;
                $port = $remoteBackendOptions['port'] ?? null;

                if (($host !== null && $host !== $backendConfig['host'])
                    || ($port !== null && $port !== $backendConfig['port'])
                ) {
                    return false;
                }
            }
        } elseif ($this->isSynchronizedConfigStructure()) {
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
     * Builds the final config for the RemoteSynchronizedCache backend: a default frontend plus a
     * page_cache frontend on a separate database, so full_page cache traffic doesn't share a
     * connection/database with every other cache type.
     *
     * @param  string $cacheBackendModel
     * @param  array  $backendConfig
     * @param  array  $envCacheConfiguration
     * @param  string $slaveConnectionBackend
     * @return array
     * @throws ConfigException
     */
    private function getSynchronizedFinalConfig(
        string $cacheBackendModel,
        array $backendConfig,
        array $envCacheConfiguration,
        string $slaveConnectionBackend
    ): array {
        $cacheCacheBackend = $this->getSynchronizedConfigStructure($cacheBackendModel, $backendConfig);
        $cacheCacheBackend['backend_options']['remote_backend_options'] = array_merge(
            $cacheCacheBackend['backend_options']['remote_backend_options'],
            $this->getSlaveConnection($envCacheConfiguration, $backendConfig, $slaveConnectionBackend)
        );

        return [
            'frontend' => [
                'default' => $cacheCacheBackend,
                'page_cache' => array_replace_recursive(
                    $cacheCacheBackend,
                    [
                        'backend_options' => [
                            'remote_backend_options' => ['database' => self::CACHE_DATABASE_PAGE_CACHE],
                        ],
                    ]
                ),
            ],
            'type' => [
                'default' => ['frontend' => 'default'],
            ],
        ];
    }

    /**
     * Checks that config contains synchronized cache model and need to use synchronized config structure.
     *
     * @return bool
     * @throws ConfigException
     */
    private function isSynchronizedConfigStructure(): bool
    {
        $redisModel = $this->getRedisBackendModel();
        $valkeyModel = $this->getValkeyBackendModel();
        return $redisModel === self::REDIS_BACKEND_REMOTE_SYNCHRONIZED_CACHE ||
        $valkeyModel === self::VALKEY_BACKEND_REMOTE_SYNCHRONIZED_CACHE;
    }

    /**
     * Checks whether the symfony_l2 backend is configured (requires Magento 2.4.9+).
     *
     * @return bool
     * @throws ConfigException
     */
    private function isSymfonyL2Structure(): bool
    {
        $redisModel = $this->getRedisBackendModel();
        $valkeyModel = $this->getValkeyBackendModel();
        return $redisModel === self::REDIS_BACKEND_SYMFONY_L2 || $valkeyModel === self::VALKEY_BACKEND_SYMFONY_L2;
    }

    /**
     * Returns the configured CACHE_REDIS_BACKEND value, resolving the 'redis' alias to
     * REDIS_BACKEND_REDIS_CACHE.
     *
     * @return string
     */
    private function getRedisBackendModel(): string
    {
        $model = (string)$this->stageConfig->get(DeployInterface::VAR_CACHE_REDIS_BACKEND);

        return $model === self::REDIS_BACKEND_ALIAS ? self::REDIS_BACKEND_REDIS_CACHE : $model;
    }

    /**
     * Returns the configured CACHE_VALKEY_BACKEND value, resolving the 'valkey' alias to
     * VALKEY_BACKEND_VALKEY_CACHE.
     *
     * @return string
     */
    private function getValkeyBackendModel(): string
    {
        $model = (string)$this->stageConfig->get(DeployInterface::VAR_CACHE_VALKEY_BACKEND);

        return $model === self::VALKEY_BACKEND_ALIAS ? self::VALKEY_BACKEND_VALKEY_CACHE : $model;
    }

    /**
     * Builds the full symfony_l2 config: default frontend (no stale) + stale_cache_enabled frontend + type mappings.
     *
     * $remoteBackend is passed in explicitly (rather than derived from $backendConfig['scheme']) because the
     * relationship's scheme reflects the wire protocol (typically 'redis' for both Redis and Valkey services,
     * since Valkey is Redis-protocol-compatible), not which service is actually behind it.
     *
     * @param  array  $backendConfig
     * @param  string $remoteBackend 'redis' or 'valkey'
     * @return array
     */
    private function getSymfonyL2ConfigStructure(array $backendConfig, string $remoteBackend): array
    {
        $remoteBackendOptions = [
            'server'          => $backendConfig['host'],
            'port'            => $backendConfig['port'],
            'database'        => self::CACHE_DATABASE_DEFAULT,
            'compression_lib' => 'gzip',
        ];

        if (!empty($backendConfig['password'])) {
            $remoteBackendOptions['password'] = (string)$backendConfig['password'];
        }

        return [
            'frontend' => [
                'default' => [
                    'backend' => self::VALKEY_BACKEND_SYMFONY_L2,
                    'backend_options' => [
                        'remote_backend' => $remoteBackend,
                        'remote_backend_options' => array_merge(
                            $remoteBackendOptions,
                            ['persistent_id' => 'magento_l2_default']
                        ),
                        'local_backend' => 'file',
                        'local_backend_options' => [
                            'cache_dir' => '/dev/shm/magento_l1',
                        ],
                    ],
                ],
                'stale_cache_enabled' => [
                    'backend' => self::VALKEY_BACKEND_SYMFONY_L2,
                    'backend_options' => [
                        'remote_backend' => $remoteBackend,
                        'remote_backend_options' => array_merge(
                            $remoteBackendOptions,
                            ['persistent_id' => 'magento_l2_stale']
                        ),
                        'local_backend' => 'file',
                        'local_backend_options' => [
                            'cache_dir' => '/dev/shm/magento_l1_stale',
                        ],
                        'use_stale_cache' => true,
                    ],
                ],
            ],
            'type' => [
                'default'                => ['frontend' => 'default'],
                'layout'                 => ['frontend' => 'stale_cache_enabled'],
                'block_html'             => ['frontend' => 'stale_cache_enabled'],
                'reflection'             => ['frontend' => 'stale_cache_enabled'],
                'config_integration'     => ['frontend' => 'stale_cache_enabled'],
                'config_integration_api' => ['frontend' => 'stale_cache_enabled'],
                'full_page'              => ['frontend' => 'stale_cache_enabled'],
                'translate'              => ['frontend' => 'stale_cache_enabled'],
            ],
        ];
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
