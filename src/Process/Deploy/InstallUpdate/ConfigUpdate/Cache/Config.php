<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Process\Deploy\InstallUpdate\ConfigUpdate\Cache;

use Magento\MagentoCloud\Config\Environment;
use Magento\MagentoCloud\Config\Stage\DeployInterface;
use Psr\Log\LoggerInterface;

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
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param Environment $environment
     * @param DeployInterface $stageConfig
     * @param LoggerInterface $logger
     */
    public function __construct(
        Environment $environment,
        DeployInterface $stageConfig,
        LoggerInterface $logger
    ) {
        $this->environment = $environment;
        $this->stageConfig = $stageConfig;
        $this->logger = $logger;
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
            if ($this->stageConfig->get(DeployInterface::VAR_REDIS_USE_SLAVE_CONNECTION)) {
                $this->logger->notice(
                    sprintf(
                        'The variable \'%s\' is ignored as you set your own cache connection in \'%s\'',
                        DeployInterface::VAR_REDIS_USE_SLAVE_CONNECTION,
                        DeployInterface::VAR_CACHE_CONFIGURATION
                    )
                );
            }
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

        $slaveConnectionData = $this->getSlaveConnection();
        if ($slaveConnectionData) {
            $redisCache['backend_options']['load_from_slave'] = $slaveConnectionData;
        }

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
            $this->logger->info('Set Redis slave connection');
            $connectionData = [
                'server' => $slaveHost,
                'port' => $redisSlaveConfig[0]['port'] ?? '',
            ];
        }

        return $connectionData;
    }
}
