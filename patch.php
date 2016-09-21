<?php

use Magento\MagentoCloud\Environment;

require_once 'src/Magento/MagentoCloud/Environment.php';
$env = new Environment();

$env->log("Copying static.php to front-static.php");
copy(Environment::MAGENTO_ROOT . 'pub/static.php', Environment::MAGENTO_ROOT . 'pub/front-static.php');

$dirName = __DIR__ . '/patches';
$dir = new DirectoryIterator($dirName);
foreach ($dir as $fileinfo) {
    if (!$fileinfo->isDot()) {
        $filename = $fileinfo->getFilename();
        $cmd = 'git apply ' . $dirName . '/' . $filename;
        $env->execute($cmd);
    }
}

copy(Environment::MAGENTO_ROOT . 'app/etc/di.xml', Environment::MAGENTO_ROOT . 'app/di.xml');
mkdir(Environment::MAGENTO_ROOT . 'app/enterprise', 0777, true);
copy(Environment::MAGENTO_ROOT . 'app/etc/enterprise/di.xml', Environment::MAGENTO_ROOT . 'app/enterprise/di.xml');

$sampleDataDir = Environment::MAGENTO_ROOT . 'vendor/magento/sample-data-media';
if (file_exists($sampleDataDir)) {
    $env->log("Sample data media found. Marshalling to pub/media.");
    $destination = Environment::MAGENTO_ROOT . '/pub/media';
    foreach (
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($sampleDataDir, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST) as $item
    ) {
        if ($item->isDir()) {
            if (!file_exists($destination . DIRECTORY_SEPARATOR . $iterator->getSubPathName())) {
                mkdir($destination . DIRECTORY_SEPARATOR . $iterator->getSubPathName());
            }
        } else {
            copy($item, $destination . DIRECTORY_SEPARATOR . $iterator->getSubPathName());
        }
    }
}
