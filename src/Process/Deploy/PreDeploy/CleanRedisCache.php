<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Process\Deploy\PreDeploy;

use Magento\MagentoCloud\Config\Environment;
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
     * @param LoggerInterface $logger
     * @param ShellInterface $shell
     * @param Environment $env
     */
    public function __construct(
        LoggerInterface $logger,
        ShellInterface $shell,
        Environment $env
    ) {
        $this->logger = $logger;
        $this->shell = $shell;
        $this->env = $env;
    }

    /**
     * Clears redis cache if redis enabled and configuration exists in MAGENTO_CLOUD_RELATIONSHIPS env variable.
     *
     * @return void
     */
    public function execute()
    {
        $redis = $this->env->getRelationship('redis');

        if (count($redis) > 0) {
            $redisHost = $redis[0]['host'];
            $redisPort = $redis[0]['port'];
            $redisCacheDb = '1';
            $this->logger->info('Clearing redis cache');
            $this->shell->execute("redis-cli -h $redisHost -p $redisPort -n $redisCacheDb flushdb");
        }
    }
}
