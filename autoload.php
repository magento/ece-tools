<?php

define('MAGENTO_ROOT', __DIR__ . '/../../../');
define('BP', __DIR__);

/**
 * This hack is to prevent Composer crash when
 * NonComposerComponentRegistration.php was moved from
 * app/etc during writable directories mounting.
 */
if (!file_exists(MAGENTO_ROOT . '/app/etc/NonComposerComponentRegistration.php') &&
    file_exists(MAGENTO_ROOT . '/init/app/etc/NonComposerComponentRegistration.php')
) {
    copy(
        MAGENTO_ROOT . '/init/app/etc/NonComposerComponentRegistration.php',
        MAGENTO_ROOT . '/app/etc/NonComposerComponentRegistration.php'
    );
}

foreach ([__DIR__ . '/../../autoload.php', __DIR__ . '/vendor/autoload.php'] as $file) {
    if (file_exists($file)) {
        return require $file;
    }
}
