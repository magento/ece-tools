<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Command\Dev\UpdateComposer;

use Magento\MagentoCloud\Filesystem\DirectoryList;
use Magento\MagentoCloud\Filesystem\Driver\File;
use Magento\MagentoCloud\Package\MagentoVersion;

class ComposerGenerator
{
    const POSSIBLE_REPOS = ['ce', 'ee', 'b2b'];
    /**
     * @var DirectoryList
     */
    private $directoryList;

    /**
     * @var MagentoVersion
     */
    private $magentoVersion;

    /**
     * @var File
     */
    private $file;

    public function __construct(
        DirectoryList $directoryList,
        MagentoVersion $magentoVersion,
        File $file
    ) {
        $this->directoryList = $directoryList;
        $this->magentoVersion = $magentoVersion;
        $this->file = $file;
    }

    /**
     * @param array $repoOptions
     * @return array
     */
    public function generate(array $repoOptions): array
    {
        $installPromGitScripts = $this->getInstallFromGitScripts($repoOptions);

        $preparePackagesScripts = [];

        foreach ($repoOptions as $repoName => $gitOption) {
            $preparePackagesScripts[] = sprintf(
                sprintf(
                    "rsync -av --exclude='app/code/Magento/' --exclude='app/i18n/' --exclude='app/design/' "
                    . "--exclude='dev/tests' --exclude='lib/internal/Magento' ./%s/ ./",
                    $repoName
                )
            );
        }

        $composer = [
            "name" => "magento/cloud-dev",
            "description" => "eCommerce Platform for Growth",
            "type" => "project",
            "version" => "{$this->magentoVersion->getVersion()}",
            "license" => [
                "OSL-3.0"
            ],
            "bin" => [
                'ce/bin/magento'
            ],
            "repositories" => [
                [
                    "type" => "path",
                    "url" => "./ce/lib/internal/Magento/Framework/",
                    "transport-options" => [
                        "symlink" => false
                    ],
                    "options" => [
                        "symlink" => false
                    ]
                ],
                [
                    "type" => "composer",
                    "url" => "https://repo.magento.com"
                ]
            ],
            "require" => [
                "magento/ece-tools" => "2002.0.*",
            ],
            "config" => [
                "use-include-path" => true
            ],
            "autoload" => [
                "psr-4" => [
                    "Magento\\Setup\\" => "setup/src/Magento/Setup/",
                ],
            ],
            "minimum-stability" => "dev",
            "prefer-stable" => true,
            "extra" => [
                "magento-force" => "override",
                "magento-deploystrategy" => "copy"
            ],
            "scripts" => [
                "install-from-git" => $installPromGitScripts,
                "prepare-packages" => $preparePackagesScripts,
                "pre-install-cmd" => [
                    "@install-from-git"
                ],
                "pre-update-cmd" => [
                    "@install-from-git"
                ],
                "post-install-cmd" => [
                    "@prepare-packages"
                ]
            ]
        ];

        foreach ($repoOptions as $repoName => $gitOption) {
            $repoComposer = $this->file->fileGetContents(
                $this->directoryList->getMagentoRoot() . '/' . $repoName . '/composer.json'
            );
            $composer["require"] = array_merge(
                $composer["require"],
                json_decode($repoComposer, true)["require"]
            );
        }

        $add = function ($dir) use (&$composer) {
            if (!file_exists($dir . '/composer.json')) {
                return;
            }

            $composer['repositories'][] = [
                "type" => "path",
                "url" => '.' . str_replace($this->directoryList->getMagentoRoot(), '', $dir)
            ];
            $dirComposer = json_decode($this->file->fileGetContents($dir . '/composer.json'), true);
            $composer["require"][$dirComposer['name']] = $dirComposer['version'];
        };

        foreach ($repoOptions as $repoName => $gitOption) {
            $baseRepoFolder = $this->directoryList->getMagentoRoot() . '/' . $repoName;
            foreach (glob($baseRepoFolder . '/app/code/Magento/*') as $dir) {
                $add($dir);
            }
            foreach (glob($baseRepoFolder . '/app/design/*/Magento/*/') as $dir) {
                $add($dir);
            }
            foreach (glob($baseRepoFolder . '/app/design/*/Magento/*/') as $dir) {
                $add($dir);
            }
        }

        return $composer;
    }

    /**
     * @param array $repoOptions
     * @return array
     */
    public function getInstallFromGitScripts(array $repoOptions): array
    {
        $installPromGitScripts = ['mkdir -p app/etc'];
        $installPromGitScripts[] = 'rm -rf ' . implode(' ', self::POSSIBLE_REPOS);

        foreach ($repoOptions as $repoName => $gitOption) {
            $gitCloneCommand = "git clone -b %s --single-branch --depth 1 %s %s";

            $installPromGitScripts[] = sprintf(
                $gitCloneCommand,
                $gitOption['branch'],
                $gitOption['repo'],
                $repoName
            );
        }

        return $installPromGitScripts;
    }
}
