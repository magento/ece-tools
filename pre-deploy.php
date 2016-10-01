<?php
/**
 * This script contains logic to cleanup outdated caches and restore the contents of mounted directories so that
 * the main deploy hook is able to start.
 */

use Magento\MagentoCloud\Environment;

require_once 'src/Magento/MagentoCloud/Environment.php';

$env = new Environment();
$env->log("Starting pre-deploy.");
// Clear redis and file caches
$relationships = $env->getRelationships();
$var = $env->getVariables();
$generatedCodeStash = isset($var["GENERATED_CODE_STASH"]) && file_exists($var["GENERATED_CODE_STASH"])
    ? $var["GENERATED_CODE_STASH"]
    : false;

if (isset($relationships['redis']) && count($relationships['redis']) > 0) {
    $redisHost = $relationships['redis'][0]['host'];
    $redisPort = $relationships['redis'][0]['port'];
    $redisCacheDb = '1'; // Matches \Magento\MagentoCloud\Console\Command\Deploy::$redisCacheDb
    $env->execute("redis-cli -h $redisHost -p $redisPort -n $redisCacheDb flushall");
}

$fileCacheDir = Environment::MAGENTO_ROOT . '/var/cache';
if (file_exists($fileCacheDir)) {
    $env->execute("rm -rf $fileCacheDir");
}

// Restore mounted directories
$env->log("Copying writable directories back.");

foreach ($env->writableDirs as $dir) {
    if (!($generatedCodeStash && $dir == 'var/generation')) {
        if (!file_exists($dir)) {
            mkdir($dir);
            $env->log(sprintf('Created directory: %s', $dir));
        }
        $env->execute(sprintf('/bin/bash -c "shopt -s dotglob; cp -R ./init/%s/* %s/ || true"', $dir, $dir));
        $env->log(sprintf('Copied directory: %s', $dir));
    } else {
        $generationDir = realpath(Environment::MAGENTO_ROOT . 'var/generation');
        if (file_exists($generationDir)) {
            $timestamp = time();
            // Must match directory name for the cleanup step in deploy command
            $oldGenerationDir = "{$generationDir}_old_{$timestamp}";
            $env->log("Clearing generated code out from $generationDir to $oldGenerationDir");
            rename($generationDir, $oldGenerationDir);
        }
        $env->log("Moving generated code from stash location $generatedCodeStash to $generationDir");
        rename($generatedCodeStash, $generationDir);
    }
}
$env->log("Pre-deploy complete.");
