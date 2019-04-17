<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Process\Deploy\PreDeploy;

use Magento\MagentoCloud\Config\Environment;
use Magento\MagentoCloud\Config\Factory\Cache as CacheConfig;
use Magento\MagentoCloud\Process\ProcessInterface;
use Magento\MagentoCloud\Shell\ShellInterface;
use Psr\Log\LoggerInterface;

/**
 * Cleans Redis cache.
 */
class CleanRedisCache implements ProcessInterface
{
    /**
     * @var Environment
     */
    private $env;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var ShellInterface
     */
    private $shell;

    /**
     * @var CacheConfig
     */
    private $cacheConfig;

    /**
     * @param LoggerInterface $logger
     * @param ShellInterface $shell
     * @param Environment $env
     * @param CacheConfig $cacheConfig
     */
    public function __construct(
        LoggerInterface $logger,
        ShellInterface $shell,
        Environment $env,
        CacheConfig $cacheConfig
    ) {
        $this->logger = $logger;
        $this->shell = $shell;
        $this->env = $env;
        $this->cacheConfig = $cacheConfig;
    }

    /**
     * Clears redis cache
     *
     * @return void
     */
    public function execute()
    {
        $cacheConfigs = $this->cacheConfig->get();

        if (!isset($cacheConfigs['frontend'])) {
            return;
        }
        foreach ($cacheConfigs['frontend'] as $cacheType => $cacheConfig) {
            if ($cacheConfig['backend'] != 'Cm_Cache_Backend_Redis') {
                continue;
            }
            $redisConfig = $cacheConfig['backend_options'];
            $this->logger->info("Clearing redis cache: $cacheType");
            $cmd = 'redis-cli';
            $cmd .= isset($redisConfig['server']) ? ' -h ' . $redisConfig['server'] : '';
            $cmd .= isset($redisConfig['port']) ? ' -p ' . $redisConfig['port'] : '';
            $cmd .= isset($redisConfig['database']) ? ' -n ' . $redisConfig['database'] : '';
            $cmd .= ' flushdb';
            $this->shell->execute($cmd);
        }
    }
}
