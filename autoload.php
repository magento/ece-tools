<?php

$rootDir = __DIR__ . '/../../../';
if (
    !file_exists($rootDir . '/app/etc/NonComposerComponentRegistration.php') &&
    file_exists($rootDir . '/init/app/etc/NonComposerComponentRegistration.php')
) {
    copy(
        $rootDir . '/init/app/etc/NonComposerComponentRegistration.php',
        $rootDir . '/app/etc/NonComposerComponentRegistration.php'
    );
}

foreach ([__DIR__ . '/../../autoload.php', __DIR__ . '/vendor/autoload.php'] as $file) {
    if (file_exists($file)) {
        require $file;
        break;
    }
}
