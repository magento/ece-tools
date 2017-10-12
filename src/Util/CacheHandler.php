<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Util;

use Magento\MagentoCloud\Filesystem\Driver\File;
use Magento\MagentoCloud\Shell\ShellInterface;
use Magento\MagentoCloud\Config\Environment;
use Magento\MagentoCloud\Filesystem\DirectoryList;
use Magento\MagentoCloud\Util\BackgroundDirectoryCleaner;
use Psr\Log\LoggerInterface;

class CacheHandler
{
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var ShellInterface
     */
    private $shell;

    /**
     * @var File
     */
    private $file;

    /**
     * @var Environment
     */
    private $environment;

    /**
     * @var DirectoryList
     */
    private $directoryList;

    /**
     * @var BackgroundDirectoryCleaner
     */
    private $cleaner;

    /**
     * @param LoggerInterface $logger
     * @param ShellInterface $shell
     * @param File $file
     * @param DirectoryList $directoryList
     * @param BackgroundDirectoryCleaner $cleaner
     */
    public function __construct(
        LoggerInterface $logger,
        ShellInterface $shell,
        File $file,
        Environment $environment,
        DirectoryList $directoryList,
        BackgroundDirectoryCleaner $cleaner
    ) {
        $this->logger = $logger;
        $this->shell = $shell;
        $this->file = $file;
        $this->environment = $environment;
        $this->directoryList = $directoryList;
        $this->cleaner = $cleaner;
    }

    /**
     * Clears redis cache if redis enabled and configuration exists in MAGENTO_CLOUD_RELATIONSHIPS env variable
     *
     * @return void
     */
    public function clearRedisCache()
    {
        $redis = $this->environment->getRelationship('redis');

        if (count($redis) > 0) {
            $redisHost = $redis[0]['host'];
            $redisPort = $redis[0]['port'];
            $redisCacheDb = '1'; // Matches \Magento\MagentoCloud\Command\Deploy::$redisCacheDb
            $this->logger->info('Clearing redis cache');
            $this->shell->execute("redis-cli -h $redisHost -p $redisPort -n $redisCacheDb flushdb");
        }
    }

    /**
     * Clears var/cache directory if such directory exists
     *
     * @return void
     */
    public function clearFilesCache()
    {
        $fileCacheDir = $this->directoryList->getMagentoRoot() . '/var/cache';
        if ($this->file->isExists($fileCacheDir)) {
            $this->logger->info('Clearing var/cache directory');
            $this->cleaner->backgroundDeleteDirectory($fileCacheDir);
        }
    }
}
