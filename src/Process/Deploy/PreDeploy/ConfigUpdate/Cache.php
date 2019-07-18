<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Process\Deploy\PreDeploy\ConfigUpdate;

use Magento\MagentoCloud\Config\Deploy\Reader as ConfigReader;
use Magento\MagentoCloud\Config\Deploy\Writer as ConfigWriter;
use Magento\MagentoCloud\Config\Factory\Cache as CacheFactory;
use Magento\MagentoCloud\Process\ProcessException;
use Magento\MagentoCloud\Process\ProcessInterface;
use Psr\Log\LoggerInterface;

/**
 * Processes cache configuration.
 */
class Cache implements ProcessInterface
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
     * @param ConfigReader $configReader
     * @param ConfigWriter $configWriter
     * @param LoggerInterface $logger
     * @param CacheFactory $cacheConfig
     */
    public function __construct(
        ConfigReader $configReader,
        ConfigWriter $configWriter,
        LoggerInterface $logger,
        CacheFactory $cacheConfig
    ) {
        $this->configReader = $configReader;
        $this->configWriter = $configWriter;
        $this->logger = $logger;
        $this->cacheConfig = $cacheConfig;
    }

    /**
     * @inheritdoc
     */
    public function execute()
    {
        $config = $this->configReader->read();
        $cacheConfig = $this->cacheConfig->get();

        if (isset($cacheConfig['frontend'])) {
            $cacheConfig['frontend'] = array_filter($cacheConfig['frontend'], function ($cacheFrontend) {
                return $cacheFrontend['backend'] != 'Cm_Cache_Backend_Redis'
                    || $this->testRedisConnection($cacheFrontend['backend_options']);
            });
        }

        if (empty($cacheConfig)) {
            $this->logger->info('Cache configuration was not found. Removing cache configuration.');
            unset($config['cache']);
        } elseif (empty($cacheConfig['frontend'])) {
            $this->logger->warning(
                'Cache is configured for a Redis service that is not available. Configuration will be ignored.'
            );
            unset($config['cache']);
        } else {
            $this->logger->info('Updating cache configuration.');
            $config['cache'] = $cacheConfig;
        }

        $this->configWriter->create($config);
    }

    /**
     * Test if a socket connection can be opened to defined backend.
     *
     * @param array $backendOptions
     *
     * @return bool
     */
    private function testRedisConnection(array $backendOptions): bool
    {
        if (!isset($backendOptions['server']) || !isset($backendOptions['port'])) {
            throw new ProcessException('Missing required Redis configuration!');
        }

        $sock = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
        $connected = @socket_connect($sock, $backendOptions['server'], $backendOptions['port']);
        socket_close($sock);

        return $connected;
    }
}
