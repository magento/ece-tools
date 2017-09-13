<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Process\Deploy\InstallUpdate\ConfigUpdate;

use Magento\MagentoCloud\Config\Environment;
use Magento\MagentoCloud\Filesystem\DirectoryList;
use Magento\MagentoCloud\Filesystem\Driver\File;
use Magento\MagentoCloud\Process\ProcessInterface;
use Psr\Log\LoggerInterface;

class EnvPhp implements ProcessInterface
{
    /**
     * @var Environment
     */
    private $environment;
    /**
     * @var File
     */
    private $file;
    /**
     * @var LoggerInterface
     */
    private $logger;
    /**
     * @var DirectoryList
     */
    private $directoryList;

    /**
     * @param Environment $environment
     * @param File $file
     * @param LoggerInterface $logger
     * @param DirectoryList $directoryList
     */
    public function __construct(
        Environment $environment,
        File $file,
        LoggerInterface $logger,
        DirectoryList $directoryList
    ) {
        $this->environment = $environment;
        $this->file = $file;
        $this->logger = $logger;
        $this->directoryList = $directoryList;
    }


    /**
     * @inheritdoc
     */
    public function execute()
    {
        $this->logger->info('Updating env.php configuration.');

        $configFileName = $this->getConfigFilePath();

        $config = include $configFileName;

        $config['db']['connection']['default']['username'] = $this->environment->getDbUser();
        $config['db']['connection']['default']['host'] = $this->environment->getDbHost();
        $config['db']['connection']['default']['dbname'] = $this->environment->getDbName();
        $config['db']['connection']['default']['password'] = $this->environment->getDbPassword();

        $config['db']['connection']['indexer']['username'] = $this->environment->getDbUser();
        $config['db']['connection']['indexer']['host'] = $this->environment->getDbHost();
        $config['db']['connection']['indexer']['dbname'] = $this->environment->getDbName();
        $config['db']['connection']['indexer']['password'] = $this->environment->getDbPassword();

        $mqConfig = $this->environment->getRelationship('mq');
        if (count($mqConfig)) {
            $amqpConfig = $mqConfig[0];
            $config['queue']['amqp']['host'] = $amqpConfig['host'];
            $config['queue']['amqp']['port'] = $amqpConfig['port'];
            $config['queue']['amqp']['user'] = $amqpConfig['username'];
            $config['queue']['amqp']['password'] = $amqpConfig['password'];
            $config['queue']['amqp']['virtualhost'] = '/';
            $config['queue']['amqp']['ssl'] = '';
        } else {
            $config = $this->removeAmqpConfig($config);
        }

        $redisConfig = $this->environment->getRelationship('redis');
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

        $updatedConfig = '<?php' . "\n" . 'return ' . var_export($config, true) . ';';

        file_put_contents($configFileName, $updatedConfig);
    }

    /**
     * Remove AMQP configuration from env.php
     *
     * @param array $config
     * @return array
     */
    private function removeAmqpConfig(array $config)
    {
        $this->logger->info('Removing AMQP configuration from env.php.');

        if (isset($config['queue']['amqp'])) {
            if (count($config['queue']) > 1) {
                unset($config['queue']['amqp']);
            } else {
                unset($config['queue']);
            }
        }

        return $config;
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
     * Return full path to environment configuration file.
     *
     * @return string The path to configuration file
     */
    private function getConfigFilePath()
    {
        return $this->directoryList->getMagentoRoot() . '/app/etc/env.php';
    }
}
