<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Step\Deploy\PreDeploy;

use Magento\MagentoCloud\App\Error;
use Magento\MagentoCloud\Config\Factory\Cache as CacheConfig;
use Magento\MagentoCloud\Step\StepException;
use Magento\MagentoCloud\Step\StepInterface;
use Magento\MagentoCloud\Service\Adapter\CredisFactory;
use Psr\Log\LoggerInterface;
use CredisException;

/**
 * Cleans Redis cache.
 *
 * @SuppressWarnings(PHPMD.CyclomaticComplexity)
 * @SuppressWarnings(PHPMD.NPathComplexity)
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
     * @param LoggerInterface $logger
     * @param CacheConfig $cacheConfig
     * @param CredisFactory $credisFactory
     */
    public function __construct(
        LoggerInterface $logger,
        CacheConfig $cacheConfig,
        CredisFactory $credisFactory
    ) {
        $this->logger = $logger;
        $this->cacheConfig = $cacheConfig;
        $this->credisFactory = $credisFactory;
    }

    /**
     * Clears redis cache
     *
     * {@inheritDoc}
     */
    public function execute(): void
    {
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

            $redisConfig = ($backend === CacheConfig::REDIS_BACKEND_REMOTE_SYNCHRONIZED_CACHE)
                ? $cacheConfig['backend_options']['remote_backend_options']
                : $cacheConfig['backend_options'];

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
}
