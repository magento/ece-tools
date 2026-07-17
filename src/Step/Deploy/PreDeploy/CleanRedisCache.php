<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Step\Deploy\PreDeploy;

use Magento\MagentoCloud\App\Error;
use Magento\MagentoCloud\Config\Factory\Cache as CacheConfig;
use Magento\MagentoCloud\Service\Redis as RedisService;
use Magento\MagentoCloud\Step\StepException;
use Magento\MagentoCloud\Step\StepInterface;
use Magento\MagentoCloud\Service\Adapter\CredisFactory;
use Psr\Log\LoggerInterface;
use CredisException;

/**
 * Cleans Redis cache.
 *
 */
class CleanRedisCache implements StepInterface
{
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var CacheConfig
     */
    private $cacheConfig;

    /**
     * @var CredisFactory
     */
    private $credisFactory;

    /**
     * @var RedisService
     */
    private $redisService;

    /**
     * @param LoggerInterface $logger
     * @param CacheConfig $cacheConfig
     * @param CredisFactory $credisFactory
     * @param RedisService $redisService
     */
    public function __construct(
        LoggerInterface $logger,
        CacheConfig $cacheConfig,
        CredisFactory $credisFactory,
        RedisService $redisService
    ) {
        $this->logger = $logger;
        $this->cacheConfig = $cacheConfig;
        $this->credisFactory = $credisFactory;
        $this->redisService = $redisService;
    }

    /**
     * Clears redis cache
     *
     * {@inheritDoc}
     */
    public function execute(): void
    {
        // Run only when Redis service relationship exists (service active)
        if (empty($this->redisService->getConfiguration())) {
            return;
        }

        $cacheConfigs = $this->cacheConfig->get();

        if (!isset($cacheConfigs['frontend'])) {
            return;
        }

        foreach ($cacheConfigs['frontend'] as $cacheType => $cacheConfig) {
            $backend = $cacheConfig['backend'];
            $customRedisBackend = $cacheConfig['_custom_redis_backend'] ?? false;

            if (!$customRedisBackend && !in_array($backend, CacheConfig::AVAILABLE_REDIS_BACKEND, true)) {
                continue;
            }

            $redisConfig = $this->getRedisConnectionConfig($cacheConfig, $backend);

            $this->logger->info('Clearing redis cache: ' . $cacheType);

            $client = $this->credisFactory->create(
                isset($redisConfig['server']) ? (string)$redisConfig['server'] : '127.0.0.1',
                isset($redisConfig['port']) ? (int)$redisConfig['port'] : 6379,
                isset($redisConfig['database']) ? (int)$redisConfig['database'] : 0,
                !empty($redisConfig['password']) ? (string)$redisConfig['password'] : null
            );

            try {
                $client->connect();
                $client->flushDb();
            } catch (CredisException $e) {
                throw new StepException($e->getMessage(), Error::DEPLOY_REDIS_CACHE_CLEAN_FAILED, $e);
            }
        }
    }

    /**
     * Returns connection settings for Redis-backed cache frontends.
     *
     * @param array  $cacheConfig
     * @param string $backend
     *
     * @return array
     */
    private function getRedisConnectionConfig(array $cacheConfig, string $backend): array
    {
        if ($backend === CacheConfig::REDIS_BACKEND_REMOTE_SYNCHRONIZED_CACHE
            || $backend === CacheConfig::REDIS_BACKEND_SYMFONY_L2
        ) {
            return $cacheConfig['backend_options']['remote_backend_options'] ?? [];
        }

        return $cacheConfig['backend_options'] ?? [];
    }
}
