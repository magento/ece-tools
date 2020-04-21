<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

return array(
    'version' => '2.2',
    'repositories' => [
        'repo' =>  [
            'type' => 'git',
            'url' => 'url',
        ],
        'vendor/library1' => [
            'type' => 'path',
            'url' => 'repo1/lib/internal/Vendor/Library1',
            'options' => [
                'symlink' => false,
            ],
        ],
        'vendor/module-module1' => [
            'type' => 'path',
            'url' => 'repo2/app/code/Vendor/Module1',
            'options' => [
                'symlink' => false,
            ],
        ],
        'vendor/theme1' => [
            'type' => 'path',
            'url' => 'repo3/app/design/Vendor/Theme1',
            'options' => [
                'symlink' => false,
            ],
        ],
    ],
    'require' => [
        'package' => '*',
        'vendor/library1' => '*@dev',
        'vendor/theme1' => '*@dev',
        'vendor/module-module1' => '*@dev',
    ],
    'scripts' =>[
        'install-from-git' => [
            'php -r"@mkdir(__DIR__ . \'/app/etc\', 0777, true);"',
            'rm -rf repo1 repo2 repo3 repo4',
            'git clone path_to_repo1 "repo1" && git --git-dir="repo1/.git" --work-tree="repo1" checkout 1.0.0',
            'git clone path_to_repo2 "repo2" && git --git-dir="repo2/.git" --work-tree="repo2" checkout 2.0.0',
            'git clone path_to_repo3 "repo3" && git --git-dir="repo3/.git" --work-tree="repo3" checkout 3.0.0',
            'git clone path_to_repo4 "repo4" && git --git-dir="repo4/.git" --work-tree="repo4" checkout 4.0.0',
        ],
        'pre-install-cmd' => [
            '@install-from-git',
        ],
        'pre-update-cmd' => [
            '@install-from-git',
        ],
        'prepare-packages' => [
            'rsync -azhm --stats --exclude=\'lib/internal/Vendor/Library1\' --exclude=\'dev/tests\' --exclude=\'.git\' --exclude=\'composer.json\' --exclude=\'composer.lock\' ./repo1/ ./',
            'rsync -azhm --stats --exclude=\'app/code/Vendor/Module1\' --exclude=\'dev/tests\' --exclude=\'.git\' --exclude=\'composer.json\' --exclude=\'composer.lock\' ./repo2/ ./',
            'rsync -azhm --stats --exclude=\'app/design/Vendor/Theme1\' --exclude=\'dev/tests\' --exclude=\'.git\' --exclude=\'composer.json\' --exclude=\'composer.lock\' ./repo3/ ./',
            'rsync -azhm --stats --exclude=\'dev/tests\' --exclude=\'.git\' --exclude=\'composer.json\' --exclude=\'composer.lock\' ./repo4/ ./',
        ],
        'post-install-cmd' => [
            '@prepare-packages',
        ],
    ],
);
