<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Process\Deploy\InstallUpdate\ConfigUpdate\Cache;

use Magento\MagentoCloud\Config\Environment;
use Magento\MagentoCloud\Config\Stage\DeployInterface;

/**
 * Returns cache configuration.
 */
class Config
{
    /**
     * @var Environment
     */
    private $environment;

    /**
     * @var DeployInterface
     */
    private $stageConfig;

    /**
     * @param Environment $environment
     * @param DeployInterface $stageConfig
     */
    public function __construct(
        Environment $environment,
        DeployInterface $stageConfig
    ) {
        $this->environment = $environment;
        $this->stageConfig = $stageConfig;
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

        if ($this->isCacheConfigurationValid($envCacheConfiguration)) {
            return $envCacheConfiguration;
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
                'database' => 1,
            ],
        ];

        return [
            'frontend' => [
                'default' => $redisCache,
                'page_cache' => $redisCache,
            ],
        ];
    }

    /**
     * Checks that given cache configuration is valid.
     *
     * @param array $cacheConfiguration
     * @return bool
     */
    private function isCacheConfigurationValid(array $cacheConfiguration): bool
    {
        return !empty($cacheConfiguration) && isset($cacheConfiguration['frontend']);
    }
}
