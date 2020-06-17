<?php

$composer = json_decode(file_get_contents(__DIR__ . '/composer.json'), true);

if (isset($composer['repositories'])) {
    $repos = array_filter($composer['repositories'], function($val) {
       return isset($val['type']) && $val['type'] == 'path';
    });
} else {
    $repos = [];
}
$packages = array_keys($repos);

function clearRequirements($dir, $packages) {
    if (!file_exists($dir . '/composer.json')) {
        return;
    }

    $composerJson = json_decode(file_get_contents($dir . '/composer.json'), true);

    foreach ($composerJson['require'] as $requireName => $requireVersion) {
        if (in_array($requireName, $packages)) {
            $composerJson['require'][$requireName] = '*@dev';
        }
    }

    file_put_contents(
        $dir . '/composer.json',
        json_encode($composerJson, JSON_PRETTY_PRINT)
    );
}

foreach ($repos as $repoName => $repoOptions) {
    $repoDir = __DIR__ .'/' . $repoOptions['url'];
    clearRequirements($repoDir, $packages);
}
