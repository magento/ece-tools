<?php
/**
 * This script contains logic to cleanup outdated caches and restore the contents of mounted directories so that
 * the main deploy hook is able to start.
 */

// Should be deleted at the end of pre-deploy, so presence of flag later indicate if something failed in the pre-deploy.
echo "Setting the pre-deploy flag." . PHP_EOL;
use Magento\MagentoCloud\Environment;
touch(Environment::PRE_DEPLOY_FLAG);

require_once 'src/Magento/MagentoCloud/Environment.php';

$env = new Environment();
$env->log("Starting pre-deploy.");

// Clear redis and file caches
$relationships = $env->getRelationships();
$var = $env->getVariables();
$useGeneratedCodeSymlink = isset($var["GENERATED_CODE_SYMLINK"]) && $var["GENERATED_CODE_SYMLINK"] == 'disabled' ? false : true;
$useStaticContentSymlink = isset($var["STATIC_CONTENT_SYMLINK"]) && $var["STATIC_CONTENT_SYMLINK"] == 'disabled' ? false : true;

if (isset($relationships['redis']) && count($relationships['redis']) > 0) {
    $redisHost = $relationships['redis'][0]['host'];
    $redisPort = $relationships['redis'][0]['port'];
    $redisCacheDb = '1'; // Matches \Magento\MagentoCloud\Console\Command\Deploy::$redisCacheDb
    $env->exec("redis-cli -h $redisHost -p $redisPort -n $redisCacheDb flushall");
}

$fileCacheDir = Environment::MAGENTO_ROOT . '/var/cache';
if (file_exists($fileCacheDir)) {
    $env->execute("rm -rf $fileCacheDir");
}

$mountedDirectories = ['app/etc', 'pub/media'];

/**
 * optionally symlink DI assets from build resources directory(var/generation to init/var/generation
 * (var/di -> init/var/di, var/generation -> init/var/generation)
 **/

$buildDir = realpath(Environment::MAGENTO_ROOT . 'init') . '/';
if ($useGeneratedCodeSymlink) {
    $varDir = realpath(Environment::MAGENTO_ROOT . 'var') . '/';
    $env->execute("rm -rf {$varDir}/di");
    $env->execute("rm -rf {$varDir}/generation");
    if (symlink($buildDir . 'var/generation', $varDir . 'generation')) {
        $env->log('Symlinked var/generation to init/var/generation');
    }

    if (symlink($buildDir . 'var/di', $varDir . 'di')) {
        $env->log('Symlinked var/di to init/var/di');
    }
} else {
    array_push($mountedDirectories, 'var/di');
    array_push($mountedDirectories, 'var/generation');
}

if ($useStaticContentSymlink) {
    $staticContentLocation = realpath(Environment::MAGENTO_ROOT . 'pub/static') . '/';
    $env->execute("rm -rf {$staticContentLocation}/*");
    $dir = new \DirectoryIterator($buildDir . 'pub/static');
    foreach ($dir as $fileInfo) {
        $fileName = $fileInfo->getFilename();
        if (!$fileInfo->isDot() && symlink($buildDir . 'pub/static/' . $fileName, $staticContentLocation . '/' . $fileName)) {
            $env->log('Symlinked ' . $staticContentLocation . '/' . $fileName . ' to ' . $buildDir . 'pub/static/' . $fileName);
        }
    }
}

// Restore mounted directories
$env->log("Copying writable directories back.");

foreach ($mountedDirectories as $dir) {
    if (!file_exists($dir)) {
        mkdir($dir);
        $env->log(sprintf('Created directory: %s', $dir));
    }
    $env->execute(sprintf('/bin/bash -c "shopt -s dotglob; cp -R ./init/%s/* %s/ || true"', $dir, $dir));
    $env->log(sprintf('Copied directory: %s', $dir));
}

$env->log("Pre-deploy complete.");
