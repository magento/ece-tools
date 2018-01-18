<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Process\Deploy\InstallUpdate\ConfigUpdate;

use Magento\MagentoCloud\Config\Environment;
use Magento\MagentoCloud\Config\Stage\DeployInterface;
use Magento\MagentoCloud\Package\PhpRedisSessionAbstractVersion;
use Magento\MagentoCloud\Process\ProcessInterface;
use Magento\MagentoCloud\Config\Deploy\Reader as ConfigReader;
use Magento\MagentoCloud\Config\Deploy\Writer as ConfigWriter;
use Psr\Log\LoggerInterface;

/**
 * @inheritdoc
 */
class Redis implements ProcessInterface
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
     * @var PhpRedisSessionAbstractVersion
     */
    private $phpRedisSessionAbstractVersion;

    /**
     * @param Environment $environment
     * @param ConfigReader $configReader
     * @param ConfigWriter $configWriter
     * @param LoggerInterface $logger
     * @param DeployInterface $stageConfig
     * @param PhpRedisSessionAbstractVersion $phpRedisSessionAbstractVersion
     */
    public function __construct(
        Environment $environment,
        ConfigReader $configReader,
        ConfigWriter $configWriter,
        LoggerInterface $logger,
        DeployInterface $stageConfig,
        PhpRedisSessionAbstractVersion $phpRedisSessionAbstractVersion
    ) {
        $this->environment = $environment;
        $this->configReader = $configReader;
        $this->configWriter = $configWriter;
        $this->logger = $logger;
        $this->stageConfig = $stageConfig;
        $this->phpRedisSessionAbstractVersion = $phpRedisSessionAbstractVersion;
    }

    /**
     * @inheritdoc
     */
    public function execute()
    {
        $redisConfig = $this->environment->getRelationship('redis');
        $config = $this->configReader->read();

        if (count($redisConfig)) {
            $this->logger->info('Updating env.php Redis cache configuration.');
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

            $config['cache'] = empty($config['cache'])
                ? $cacheConfig
                : array_replace_recursive($config['cache'], $cacheConfig);

            $redisSessionConfig = [
                'host' => $redisConfig[0]['host'],
                'port' => $redisConfig[0]['port'],
                'database' => 0,
                'disable_locking' => (int)$this->isLockingDisabled(),
            ];
            $config['session'] = [
                'save' => 'redis',
                'redis' => array_replace_recursive(
                    $config['session']['redis'] ?? [],
                    $redisSessionConfig
                ),
            ];
        } else {
            $config = $this->removeRedisConfiguration($config);
        }

        $this->configWriter->write($config);
    }

    /**
     * Clears configuration from redis usages.
     *
     * @param array $config An array of application configuration
     * @return array
     */
    private function removeRedisConfiguration($config)
    {
        $this->logger->info('Removing redis cache and session configuration from env.php.');

        if (isset($config['session']['save']) && $config['session']['save'] == 'redis') {
            $config['session']['save'] = 'db';
            if (isset($config['session']['redis'])) {
                unset($config['session']['redis']);
            }
        }

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
     * Checks if disable_locking options is enabled.
     * By default this method returns true and disable_locking options will be set to 1.
     * For turning this option off environment variable 'REDIS_SESSION_DISABLE_LOCKING' should have value 'disabled'.
     *
     * From version 1.3.4 of package colinmollenhour/php-redis-session-abstract disable locking flow was inverted
     * so for version greater than 1.3.3 this method inverts the result.
     *
     * @return bool
     */
    private function isLockingDisabled(): bool
    {
        $isLockingDisabled = $this->stageConfig->get(DeployInterface::VAR_REDIS_SESSION_DISABLE_LOCKING);

        try {
            if ($this->phpRedisSessionAbstractVersion->isGreaterThan('1.3.3')) {
                $isLockingDisabled = !$isLockingDisabled;
            }
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
        }

        return $isLockingDisabled;
    }
}
