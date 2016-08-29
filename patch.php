<?php

require_once 'src/Magento/MagentoCloud/Environment.php';
$env = new \Magento\MagentoCloud\Environment();

$dirName = __DIR__ . '/patches';
$dir = new DirectoryIterator($dirName);
foreach ($dir as $fileinfo) {
    if (!$fileinfo->isDot()) {
        $filename = $fileinfo->getFilename();
        $cmd = 'git apply ' . $dirName . '/' . $filename;
        $env->execute($cmd);
    }
}

$root = __DIR__ . '/../../../';

copy($root . 'app/etc/di.xml', $root . 'app/di.xml');
mkdir($root . 'app/enterprise');
copy($root . 'app/etc/enterprise/di.xml', $root . 'app/enterprise/di.xml');

$sampleDataDir = $root . 'vendor/magento/sample-data-media';
if (file_exists($sampleDataDir)) {
    $env->log("Sample data media found. Marshalling to pub/media.");
    $destination = $root . '/pub/media';
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
