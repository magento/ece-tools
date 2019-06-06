<?php
$repos = array (
  'repo1' => 
  array (
    'branch' => '1.2',
    'repo' => 'https://token@repo1.com',
  ),
  'repo2' => 
  array (
    'branch' => '2.2',
    'repo' => 'https://token@repo2.com',
  ),
  'repo3' => 
  array (
    'branch' => '2.3',
    'repo' => 'https://token@repo2.com',
    'type' => 'single-package',
  ),
);

function clearRequirements($dir) {
    if (!file_exists($dir . '/composer.json')) {
        return;
    }

    $composerJson = json_decode(file_get_contents($dir . '/composer.json'), true);

    foreach ($composerJson['require'] as $requireName => $requireVersion) {
        if (preg_match('{^(magento\/|elasticsearch\/)}i', $requireName)) {
            unset($composerJson['require'][$requireName]);
        }
    }

    file_put_contents(
        $dir . '/composer.json',
        json_encode($composerJson, JSON_PRETTY_PRINT)
    );
}

foreach ($repos as $repoName => $repoOptions) {
    $repoDir = __DIR__ .'/' . $repoName;

    if (isset($repoOptions['type']) && $repoOptions['type'] == 'single-package') {
        clearRequirements($repoDir);
        continue;
    }

    foreach (glob($repoDir . '/app/code/Magento/*') as $moduleDir) {
        clearRequirements($moduleDir);
    }
}
