<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Step\Deploy\PreDeploy\ConfigUpdate;

use Magento\MagentoCloud\App\Error;
use Magento\MagentoCloud\Config\Factory\Cache as CacheFactory;
use Magento\MagentoCloud\Config\Magento\Env\ReaderInterface as ConfigReader;
use Magento\MagentoCloud\Config\Magento\Env\WriterInterface as ConfigWriter;
use Magento\MagentoCloud\Config\Stage\DeployInterface;
use Magento\MagentoCloud\Filesystem\FileSystemException;
use Magento\MagentoCloud\Step\StepException;
use Magento\MagentoCloud\Step\StepInterface;
use Magento\MagentoCloud\Package\MagentoVersion;
use Magento\MagentoCloud\Package\UndefinedPackageException;
use Psr\Log\LoggerInterface;

/**
 * Processes cache configuration.
 *
 */
class Cache implements StepInterface
{
    /**
     * @var LoggerInterface
     */
    private LoggerInterface $logger;

    /**
     * @var ConfigWriter
     */
    private ConfigWriter $configWriter;

    /**
     * @var ConfigReader
     */
    private ConfigReader $configReader;

    /**
     * @var CacheFactory
     */
    private CacheFactory $cacheConfig;

    /**
     * @var MagentoVersion
     */
    private MagentoVersion $magentoVersion;

    /**
     * @var DeployInterface
     */
    private DeployInterface $stageConfig;

    /**
     * Constructor method.
     *
     * @param ConfigReader    $configReader
     * @param ConfigWriter    $configWriter
     * @param LoggerInterface $logger
     * @param CacheFactory    $cacheConfig
     * @param MagentoVersion  $magentoVersion
     * @param DeployInterface $stageConfig
     */
    public function __construct(
        ConfigReader $configReader,
        ConfigWriter $configWriter,
        LoggerInterface $logger,
        CacheFactory $cacheConfig,
        MagentoVersion $magentoVersion,
        DeployInterface $stageConfig
    ) {
        $this->configReader = $configReader;
        $this->configWriter = $configWriter;
        $this->logger = $logger;
        $this->cacheConfig = $cacheConfig;
        $this->magentoVersion = $magentoVersion;
        $this->stageConfig = $stageConfig;
    }

    /**
     */
    public function execute()
    {
        try {
            $config        = $this->configReader->read();
            $cacheConfig   = $this->cacheConfig->get();
            $graphqlConfig = $config['cache']['graphql'] ?? [];
            $luaConfig     = (bool)$this->stageConfig->get(DeployInterface::VAR_USE_LUA);
            $luaConfigKey  = (bool)$this->stageConfig->get(DeployInterface::VAR_LUA_KEY);

            if (isset($cacheConfig['frontend'])) {
                $cacheConfig['frontend'] = array_filter(
                    $cacheConfig['frontend'],
                    function ($cacheFrontend) {
                        $backend = $cacheFrontend['backend'];
                        $customCacheBackend = $cacheFrontend['_custom_valkey_backend']
                            ?? $cacheFrontend['_custom_redis_backend']
                            ?? false;
                        $this->checkBackendModel($backend);

                        if (!$customCacheBackend && !in_array($backend, CacheFactory::AVAILABLE_REDIS_BACKEND, true)) {
                            return true;
                        }
                        $backendOptions = ($backend === CacheFactory::REDIS_BACKEND_REMOTE_SYNCHRONIZED_CACHE)
                            ? $cacheFrontend['backend_options']['remote_backend_options']
                            : $cacheFrontend['backend_options'];
                        return $this->testCacheConnection($backendOptions);
                    }
                );

                foreach (array_keys($cacheConfig['frontend']) as $cacheConfigType) {
                    unset($cacheConfig['frontend'][$cacheConfigType]['_custom_redis_backend']);
                    unset($cacheConfig['frontend'][$cacheConfigType]['_custom_valkey_backend']);
                }
            }

            if (empty($cacheConfig)) {
                $this->logger->info('Cache configuration was not found. Removing cache configuration.');
                unset($config['cache']);
            } elseif (empty($cacheConfig['frontend'])) {
                $isRedisConfigured = !empty($cacheConfig['frontend']['default']['_custom_redis_backend']);
                $isValkeyConfigured = !empty($cacheConfig['frontend']['default']['_custom_valkey_backend']);
                if ($isRedisConfigured) {
                    $this->logger->warning(
                        'Cache is configured for a Redis service that is not available.
                             Configuration will be ignored.',
                        ['errorCode' => Error::WARN_REDIS_SERVICE_NOT_AVAILABLE]
                    );
                }

                if ($isValkeyConfigured) {
                    $this->logger->warning(
                        'Cache is configured for a Valkey service that is not available. 
                            Configuration will be ignored.',
                        ['errorCode' => Error::WARN_VALKEY_SERVICE_NOT_AVAILABLE]
                    );
                }

                unset($config['cache']);
            } else {
                if (isset($cacheConfig['frontend']['default'])) {
                    $cacheConfig['frontend']['default']['backend_options']['_useLua'] = $luaConfigKey;
                    $cacheConfig['frontend']['default']['backend_options']['use_lua'] = $luaConfig;
                }
                $this->logger->info('Updating cache configuration.');
                $config['cache'] = $cacheConfig;
            }

            if (!empty($graphqlConfig)) {
                $config['cache']['graphql'] = $graphqlConfig;
            }

            $this->configWriter->create($config);
        } catch (FileSystemException $e) {
            throw new StepException($e->getMessage(), Error::DEPLOY_ENV_PHP_IS_NOT_WRITABLE);
        }
    }

    /**
     * Checks that configured backend model can be used with installed magento version.
     *
     * @param  string $backend
     * @throws StepException
     */
    private function checkBackendModel(string $backend): void
    {
        $notAllowedRedisBackend = [
            CacheFactory::REDIS_BACKEND_REDIS_CACHE,
            CacheFactory::REDIS_BACKEND_REMOTE_SYNCHRONIZED_CACHE
        ];
        $AllowedRedisBackendCheck = [
            CacheFactory::REDIS_BACKEND_CM_CACHE,
            CacheFactory::REDIS_BACKEND_REDIS_CACHE,
            CacheFactory::REDIS_BACKEND_REMOTE_SYNCHRONIZED_CACHE
        ];

        $isValkeyEnabled =  $this->cacheConfig->isValkeyEnabled();
        $isRedisEnabled =  $this->cacheConfig->isRedisEnabled();
        try {
            if (!$this->magentoVersion->isGreaterOrEqual('2.4.5') && ($isValkeyEnabled['scheme'] ?? '') === 'valkey') {
                $this->logger->warning(
                    sprintf(
                        'Magento version \'%s\' does not support Valkey backend model \'%s\'',
                        $this->magentoVersion->getVersion(),
                        $backend
                    )
                );
            }
            if (($isRedisEnabled['scheme'] ?? '') === 'redis'
                && $this->magentoVersion->isGreaterOrEqual('2.4.8')
            ) {
                $this->logger->warning(
                    sprintf(
                        'Magento version \'%s\' recommends using Valkey as the cache backend instead of \'%s\'',
                        $this->magentoVersion->getVersion(),
                        $backend
                    )
                );
            }

            if (in_array($backend, $notAllowedRedisBackend, true)
                && !$this->magentoVersion->isGreaterOrEqual('2.3.0')
            ) {
                throw new StepException(
                    sprintf(
                        'Magento version \'%s\' does not support Redis backend model \'%s\'',
                        $this->magentoVersion->getVersion(),
                        $backend
                    )
                );
            }
        } catch (UndefinedPackageException $exception) {
            throw new StepException(
                $exception->getMessage(),
                $exception->getCode(),
                $exception
            );
        }
    }

    /**
     * Test if a socket connection can be opened to defined backend.
     *
     * @param array $backendOptions
     *
     * @return bool
     * @throws StepException
     */
    private function testCacheConnection(array $backendOptions): bool
    {
        if (empty($backendOptions['server'])) {
            throw new StepException(
                'Missing required Redis or Valkey configuration \'server\'!',
                Error::DEPLOY_WRONG_CACHE_CONFIGURATION
            );
        }
        $address = $backendOptions['server'];
        if (!isset($backendOptions['port'])) {
            preg_match('#^(.{1,4}://)?([^:]+)(:([0-9]+))?#', $address, $matches);
            if (!isset($matches[4])) {
                throw new StepException(
                    'Missing required Redis or Valkey configuration \'port\'!',
                    Error::DEPLOY_WRONG_CACHE_CONFIGURATION
                );
            }
            $address = $matches[2];
            $port = $matches[4];
        } else {
            $port = $backendOptions['port'];
        }

        $sock = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
        $connected = @socket_connect(
            $sock,
            (string)$address,
            (int)$port
        );
        socket_close($sock);

        return $connected;
    }
}
