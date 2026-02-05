<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Step\Deploy\PreDeploy;

use Magento\MagentoCloud\App\Error;
use Magento\MagentoCloud\Config\Factory\Cache as CacheConfig;
use Magento\MagentoCloud\Service\Valkey as ValkeyService;
use Magento\MagentoCloud\Step\StepException;
use Magento\MagentoCloud\Step\StepInterface;
use Magento\MagentoCloud\Service\Adapter\CvalkeyFactory;
use Psr\Log\LoggerInterface;
use CredisException;

/**
 * Cleans Valkey cache.
 *
 */
class CleanValkeyCache implements StepInterface
{
    /**
     * @var LoggerInterface
     */
    private LoggerInterface $logger;

    /**
     * @var CacheConfig
     */
    private CacheConfig $cacheConfig;

    /**
     * @var CvalkeyFactory
     */
    private CvalkeyFactory $cvalkeyFactory;

    /**
     * @var ValkeyService
     */
    private ValkeyService $valkeyService;

    /**
     * @param LoggerInterface $logger
     * @param CacheConfig $cacheConfig
     * @param CvalkeyFactory $cvalkeyFactory
     * @param ValkeyService $valkeyService
     */
    public function __construct(
        LoggerInterface $logger,
        CacheConfig $cacheConfig,
        CvalkeyFactory $cvalkeyFactory,
        ValkeyService $valkeyService
    ) {
        $this->logger = $logger;
        $this->cacheConfig = $cacheConfig;
        $this->cvalkeyFactory = $cvalkeyFactory;
        $this->valkeyService = $valkeyService;
    }

    /**
     * Clears redis cache
     *
     * {@inheritDoc}
     */
    public function execute(): void
    {
        // Run only when Valkey service relationship exists (service active)
        if (empty($this->valkeyService->getConfiguration())) {
            return;
        }

        $cacheConfigs = $this->cacheConfig->get();

        if (!isset($cacheConfigs['frontend'])) {
            return;
        }

        foreach ($cacheConfigs['frontend'] as $cacheType => $cacheConfig) {
            $backend = $cacheConfig['backend'];
            $customValkeyBackend = $cacheConfig['_custom_valkey_backend'] ?? false;

            if (!$customValkeyBackend && !in_array($backend, CacheConfig::AVAILABLE_VALKEY_BACKEND, true)) {
                continue;
            }

            $valkeyConfig = ($backend === CacheConfig::VALKEY_BACKEND_REMOTE_SYNCHRONIZED_CACHE)
                ? $cacheConfig['backend_options']['remote_backend_options']
                : $cacheConfig['backend_options'];

            $this->logger->info('Clearing valkey cache: ' . $cacheType);

            $client = $this->cvalkeyFactory->create(
                isset($valkeyConfig['server']) ? (string)$valkeyConfig['server'] : '127.0.0.1',
                isset($valkeyConfig['port']) ? (int)$valkeyConfig['port'] : 6379,
                isset($valkeyConfig['database']) ? (int)$valkeyConfig['database'] : 0,
                !empty($valkeyConfig['password']) ? (string)$valkeyConfig['password'] : null
            );

            try {
                $client->connect();
                $client->flushDb();
            } catch (CredisException $e) {
                throw new StepException($e->getMessage(), Error::DEPLOY_VALKEY_CACHE_CLEAN_FAILED, $e);
            }
        }
    }
}
