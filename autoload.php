<?php

function includeIfExists($className)
{
    $classNamePath = __DIR__ . '/src/' . str_replace('\\', '/', $className) .'.php';

    if (file_exists($classNamePath)) {
        return include $classNamePath;
    } else {
        $msg = 'Cannot load dependency ' . $className .PHP_EOL;
        fwrite(STDERR, $msg);
        exit(1);
    }
}
