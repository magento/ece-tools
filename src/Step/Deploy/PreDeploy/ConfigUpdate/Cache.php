<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Step\Deploy\PreDeploy\ConfigUpdate;

use Magento\MagentoCloud\App\Error;
use Magento\MagentoCloud\Config\Magento\Env\ReaderInterface as ConfigReader;
use Magento\MagentoCloud\Config\Magento\Env\WriterInterface as ConfigWriter;
use Magento\MagentoCloud\Config\Factory\Cache as CacheFactory;
use Magento\MagentoCloud\Filesystem\FileSystemException;
use Magento\MagentoCloud\Step\StepException;
use Magento\MagentoCloud\Step\StepInterface;
use Psr\Log\LoggerInterface;
use Magento\MagentoCloud\Package\MagentoVersion;
use Magento\MagentoCloud\Config\Stage\DeployInterface;

/**
 * Processes cache configuration.
 *
 * @SuppressWarnings(PHPMD.CyclomaticComplexity)
 */
class Cache implements StepInterface
{
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var ConfigWriter
     */
    private $configWriter;

    /**
     * @var ConfigReader
     */
    private $configReader;

    /**
     * @var CacheFactory
     */
    private $cacheConfig;

    /**
     * @var MagentoVersion
     */
    private $magentoVersion;

    /**
     * @var DeployInterface
     */
    private $stageConfig;

    /**
     * @param ConfigReader $configReader
     * @param ConfigWriter $configWriter
     * @param LoggerInterface $logger
     * @param CacheFactory $cacheConfig
     * @param MagentoVersion $magentoVersion
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
     * @inheritdoc
     */
    public function execute()
    {
        try {
            $config = $this->configReader->read();
            $cacheConfig = $this->cacheConfig->get();
            $graphqlConfig = $config['cache']['graphql'] ?? [];
            $luaConfig = (boolean)$this->stageConfig->get(DeployInterface::VAR_USE_LUA);
            $luaConfigKey = (boolean)$this->stageConfig->get(DeployInterface::VAR_LUA_KEY);

            if (isset($cacheConfig['frontend'])) {
                $cacheConfig['frontend'] = array_filter($cacheConfig['frontend'], function ($cacheFrontend) {
                    $backend = $cacheFrontend['backend'];
                    $customRedisBackend = $cacheFrontend['_custom_redis_backend'] ?? false;
                    $this->checkBackendModel($backend);

                    if (!$customRedisBackend && !in_array($backend, CacheFactory::AVAILABLE_REDIS_BACKEND, true)) {
                        return true;
                    }

                    $backendOptions = ($backend === CacheFactory::REDIS_BACKEND_REMOTE_SYNCHRONIZED_CACHE)
                            ? $cacheFrontend['backend_options']['remote_backend_options']
                            : $cacheFrontend['backend_options'];

                    return $this->testRedisConnection($backendOptions);
                });

                foreach (array_keys($cacheConfig['frontend']) as $cacheConfigType) {
                    unset($cacheConfig['frontend'][$cacheConfigType]['_custom_redis_backend']);
                }
            }

            if (empty($cacheConfig)) {
                $this->logger->info('Cache configuration was not found. Removing cache configuration.');
                unset($config['cache']);
            } elseif (empty($cacheConfig['frontend'])) {
                $this->logger->warning(
                    'Cache is configured for a Redis service that is not available. Configuration will be ignored.',
                    ['errorCode' => Error::WARN_REDIS_SERVICE_NOT_AVAILABLE]
                );
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
     * @param string $backend
     * @throws StepException
     */
    private function checkBackendModel(string $backend): void
    {
        $notAllowedBackend = [
            CacheFactory::REDIS_BACKEND_REDIS_CACHE,
            CacheFactory::REDIS_BACKEND_REMOTE_SYNCHRONIZED_CACHE
        ];

        try {
            if (in_array($backend, $notAllowedBackend, true) && !$this->magentoVersion->isGreaterOrEqual('2.3.0')) {
                throw new StepException(
                    sprintf(
                        'Magento version \'%s\' does not support Redis backend model \'%s\'',
                        $this->magentoVersion->getVersion(),
                        $backend
                    )
                );
            }
        } catch (\Magento\MagentoCloud\Package\UndefinedPackageException $exception) {
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
    private function testRedisConnection(array $backendOptions): bool
    {
        if (empty($backendOptions['server'])) {
            throw new StepException(
                'Missing required Redis configuration \'server\'!',
                Error::DEPLOY_WRONG_CACHE_CONFIGURATION
            );
        }
        $address = $backendOptions['server'];
        if (!isset($backendOptions['port'])) {
            preg_match('#^(.{1,4}://)?([^:]+)(:([0-9]+))?#', $address, $matches);
            if (!isset($matches[4])) {
                throw new StepException(
                    'Missing required Redis configuration \'port\'!',
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
