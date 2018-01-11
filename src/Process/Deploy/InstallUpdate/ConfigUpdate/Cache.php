<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Process\Deploy\InstallUpdate\ConfigUpdate;

use Magento\MagentoCloud\Config\Environment;
use Magento\MagentoCloud\Config\Stage\DeployInterface;
use Magento\MagentoCloud\Process\ProcessInterface;
use Magento\MagentoCloud\Config\Deploy\Reader as ConfigReader;
use Magento\MagentoCloud\Config\Deploy\Writer as ConfigWriter;
use Psr\Log\LoggerInterface;

/**
 * Processes cache configuration.
 */
class Cache implements ProcessInterface
{
    /**
     * @var Environment
     */
    private $environment;

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
     * @var DeployInterface
     */
    private $stageConfig;

    /**
     * @param Environment $environment
     * @param ConfigReader $configReader
     * @param ConfigWriter $configWriter
     * @param LoggerInterface $logger
     * @param DeployInterface $stageConfig
     */
    public function __construct(
        Environment $environment,
        ConfigReader $configReader,
        ConfigWriter $configWriter,
        LoggerInterface $logger,
        DeployInterface $stageConfig
    ) {
        $this->environment = $environment;
        $this->configReader = $configReader;
        $this->configWriter = $configWriter;
        $this->logger = $logger;
        $this->stageConfig = $stageConfig;
    }

    /**
     * @inheritdoc
     */
    public function execute()
    {
        $config = $this->configReader->read();
        $envCacheConfiguration = (array)$this->stageConfig->get(DeployInterface::VAR_CACHE_CONFIGURATION);

        if ($this->isCacheConfigurationValid($envCacheConfiguration)) {
            $this->logger->info('Updating env.php cache configuration.');
            $config['cache'] = $envCacheConfiguration;
        } elseif (count($redisConfig = $this->environment->getRelationship('redis'))) {
            $this->logger->info('Updating env.php Redis cache configuration.');
            $cacheConfig = $this->makeRedisCacheConfiguration($redisConfig);
            $config['cache'] = empty($config['cache'])
                ? $cacheConfig
                : array_replace_recursive($config['cache'], $cacheConfig);
        } else {
            $config = $this->removeRedisCacheConfiguration($config);
        }

        $this->configWriter->write($config);
    }

    /**
     * Clears cache configuration from redis usages.
     *
     * @param array $config An array of application configuration
     * @return array
     */
    private function removeRedisCacheConfiguration($config)
    {
        $this->logger->info('Removing redis cache configuration from env.php.');

        if (isset($config['cache']['frontend'])) {
            foreach ($config['cache']['frontend'] as $cacheName => $cacheData) {
                if (isset($cacheData['backend']) && $cacheData['backend'] == 'Cm_Cache_Backend_Redis') {
                    unset($config['cache']['frontend'][$cacheName]);
                }
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
        return !empty($cacheConfiguration);
    }

    /**
     * Makes redis cache configuration from relationship config.
     *
     * @param array $redisConfig
     * @return array
     */
    private function makeRedisCacheConfiguration(array $redisConfig): array
    {
        $redisCache = [
            'backend' => 'Cm_Cache_Backend_Redis',
            'backend_options' => [
                'server' => $redisConfig[0]['host'],
                'port' => $redisConfig[0]['port'],
                'database' => 1,
            ],
        ];
        $cacheConfig = [
            'frontend' => [
                'default' => $redisCache,
                'page_cache' => $redisCache,
            ],
        ];

        return $cacheConfig;
    }
}
