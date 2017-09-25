<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
define('ECE_BP', __DIR__);

/**
 * This hack is to prevent Composer crash when 'NonComposerComponentRegistration.php'
 * was moved from app/etc during writable directories mounting.
 */
$magentoRoot = __DIR__ . '/../../../';

if (!file_exists($magentoRoot . '/app/etc/NonComposerComponentRegistration.php') &&
    file_exists($magentoRoot . '/init/app/etc/NonComposerComponentRegistration.php')
) {
    copy(
        $magentoRoot . '/init/app/etc/NonComposerComponentRegistration.php',
        $magentoRoot . '/app/etc/NonComposerComponentRegistration.php'
    );
}

foreach ([__DIR__ . '/../../autoload.php', __DIR__ . '/vendor/autoload.php'] as $file) {
    if (file_exists($file)) {
        return require $file;
    }
}
