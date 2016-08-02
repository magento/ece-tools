<?php
/**
 * This script contains logic to cleanup outdated caches and restore the contents of mounted directories so that
 * the main deploy hook is able to start.
 */

require_once 'src/Magento/MagentoCloud/Environment.php';

$env = new \Magento\MagentoCloud\Environment();

// Clear redis and file caches
$relationships = $env->getRelationships();

if (isset($relationships['redis']) && count($relationships['redis']) > 0) {
    $redisHost = $relationships['redis'][0]['host'];
    $redisPort = $relationships['redis'][0]['port'];
    $redisCacheDb = '1'; // Matches \Magento\MagentoCloud\Console\Command\Deploy::$redisCacheDb
    exec("redis-cli -h $redisHost -p $redisPort -n $redisCacheDb flushall");
}

$fileCacheDir = \Magento\MagentoCloud\Environment::MAGENTO_ROOT . '/var/cache';
if (file_exists($fileCacheDir)) {
    $env->execute("rm -rf $fileCacheDir");
}

// Restore mounted directories
$env->log("Copying writable directories back.");

foreach ($env->writableDirs as $dir) {
    if (!file_exists($dir)) {
        mkdir($dir);
        $env->log(sprintf('Created directory: %s', $dir));
    }
    $env->execute(sprintf('/bin/bash -c "shopt -s dotglob; cp -R ./init/%s/* %s/ || true"', $dir, $dir));
    $env->log(sprintf('Copied directory: %s', $dir));
}
