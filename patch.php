<?php

$dirName = __DIR__ . '/patches';
$dir = new DirectoryIterator($dirName);
foreach ($dir as $fileinfo) {
    if (!$fileinfo->isDot()) {
        $cmd = 'git apply ' . $dirName . '/' . $fileinfo->getFilename();
            $output = '';
        $status = '';
        exec($cmd, $output, $status);
    }
}

$root = __DIR__ . '/../../../';
copy($root . 'app/etc/NonComposerComponentRegistration.php', $root . 'app/NonComposerComponentRegistration.php');
copy($root . 'app/etc/di.xml', $root . 'app/di.xml');
copy($root . 'app/etc/enterprise', $root . 'app/enterprise');
