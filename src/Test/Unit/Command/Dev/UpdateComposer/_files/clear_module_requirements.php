<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

$repos = array (
  'package1' => 
  array (
    'type' => 'path',
    'url' => 'repo1/app/code/package1',
  ),
  'package2' => 
  array (
    'type' => 'path',
    'url' => 'repo1/app/code/package2',
  ),
  'package6' => 
  array (
    'type' => 'path',
    'url' => 'repo2/app/code/package6',
  ),
);
$packages = var_export(array_keys($repos), true);

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
