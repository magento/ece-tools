<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Process\Deploy\InstallUpdate\ConfigUpdate;

use Magento\MagentoCloud\Config\Environment;
use Magento\MagentoCloud\Process\ProcessInterface;
use Magento\MagentoCloud\Util\ConfigWriter;
use Magento\MagentoCloud\Config\Deploy as DeployConfig;
use Psr\Log\LoggerInterface;

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
     * @var DeployConfig
     */
    private $deployConfig;

    /**
     * @param Environment $environment
     * @param ConfigWriter $configWriter
     * @param LoggerInterface $logger
     * @param DeployConfig $deployConfig
     */
    public function __construct(
        Environment $environment,
        ConfigWriter $configWriter,
        LoggerInterface $logger,
        DeployConfig $deployConfig
    ) {
        $this->environment = $environment;
        $this->logger = $logger;
        $this->configWriter = $configWriter;
        $this->deployConfig = $deployConfig;
    }

    /**
     * @inheritdoc
     */
    public function execute()
    {
        $redisConfig = $this->environment->getRelationship('redis');
        $config = $this->deployConfig->getConfig();

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
            $config['cache'] = [
                'frontend' => [
                    'default' => $redisCache,
                    'page_cache' => $redisCache,
                ],
            ];
            $config['session'] = [
                'save' => 'redis',
                'redis' => [
                    'host' => $redisConfig[0]['host'],
                    'port' => $redisConfig[0]['port'],
                    'database' => 0,
                ],
            ];
        } else {
            $config = $this->removeRedisConfiguration($config);
        }

        $config['backend']['frontName'] = $this->environment->getAdminUrl();
        $config['resource']['default_setup']['connection'] = 'default';

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
}
