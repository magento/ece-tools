<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Step\Deploy\PreDeploy\ConfigUpdate;

use Magento\MagentoCloud\Config\Magento\Env\ReaderInterface as ConfigReader;
use Magento\MagentoCloud\Config\Magento\Env\WriterInterface as ConfigWriter;
use Magento\MagentoCloud\Config\Factory\Cache as CacheFactory;
use Magento\MagentoCloud\Step\StepException;
use Magento\MagentoCloud\Step\StepInterface;
use Psr\Log\LoggerInterface;

/**
 * Processes cache configuration.
 */
class Cache implements StepInterface
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
                return $cacheFrontend['backend'] !== 'Cm_Cache_Backend_Redis'
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
     * @throws StepException
     */
    private function testRedisConnection(array $backendOptions): bool
    {
        if (!isset($backendOptions['server'], $backendOptions['port'])) {
            throw new StepException('Missing required Redis configuration!');
        }

        $sock = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
        $connected = @socket_connect(
            $sock,
            (string)$backendOptions['server'],
            (int)$backendOptions['port']
        );
        socket_close($sock);

        return $connected;
    }
}
