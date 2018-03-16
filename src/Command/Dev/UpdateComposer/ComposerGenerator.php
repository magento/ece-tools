<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Command\Dev\UpdateComposer;

use Magento\MagentoCloud\Filesystem\DirectoryList;
use Magento\MagentoCloud\Filesystem\Driver\File;
use Magento\MagentoCloud\Package\MagentoVersion;

/**
 * Generates composer.json data for installation from git.
 */
class ComposerGenerator
{
    const CE_REPO = 'ce';
    const EE_REPO = 'ee';
    const B2B_REPO = 'ee';

    const POSSIBLE_REPOS = [self::CE_REPO, self::EE_REPO, self::B2B_REPO];
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

    /**
     * @param DirectoryList $directoryList
     * @param MagentoVersion $magentoVersion
     * @param File $file
     */
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
     * Generates composer.json data for installation from git.
     *
     * @param array $repoOptions
     * @return array
     */
    public function generate(array $repoOptions): array
    {
        $composer = $this->getBaseComposer($repoOptions);

        $rootComposerJsonPath = $this->directoryList->getMagentoRoot() . '/composer.json';
        if ($this->file->isExists($rootComposerJsonPath)) {
            $rootComposer = json_decode($this->file->fileGetContents($rootComposerJsonPath), true);
            $composer['require'] += $rootComposer['require'];
            $composer['repositories'] += $rootComposer['repositories'];
        } else {
            $composer['require'] += ["magento/ece-tools" => "2002.0.*"];
        }

        foreach ($repoOptions as $repoName => $gitOption) {
            $repoComposerJsonPath = $this->directoryList->getMagentoRoot() . '/' . $repoName . '/composer.json';
            if (!$this->file->isExists($repoComposerJsonPath)) {
                continue;
            }

            $repoComposer = $this->file->fileGetContents($repoComposerJsonPath);
            $composer['require'] = array_merge(
                $composer['require'],
                json_decode($repoComposer, true)['require']
            );
        }

        $add = function ($dir) use (&$composer) {
            if (!$this->file->isExists($dir . '/composer.json')) {
                return;
            }

            $composer['repositories'][] = [
                "type" => "path",
                "url" => ltrim(str_replace($this->directoryList->getMagentoRoot(), '', $dir), '/'),
                "options" => [
                    "symlink" => false
                ]
            ];
            $dirComposer = json_decode($this->file->fileGetContents($dir . '/composer.json'), true);
            $composer['require'][$dirComposer['name']] = $dirComposer['version'];
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
        $installFromGitScripts = ['mkdir -p app/etc'];
        $installFromGitScripts[] = 'rm -rf ' . implode(' ', self::POSSIBLE_REPOS);

        foreach ($repoOptions as $repoName => $gitOption) {
            $gitCloneCommand = "git clone -b %s --single-branch --depth 1 %s %s";

            $installFromGitScripts[] = sprintf(
                $gitCloneCommand,
                $gitOption['branch'],
                $gitOption['repo'],
                $repoName
            );
        }

        if (isset($repoOptions[self::EE_REPO])) {
            $installFromGitScripts[] = 'cp -R ./ee/lib/internal/Magento/Framework/*'
                . ' ./ce/lib/internal/Magento/Framework/';
        }

        return $installFromGitScripts;
    }

    /**
     * Returns base skeleton for composer.json.
     *
     * @param array $repoOptions
     * @return array
     */
    protected function getBaseComposer(array $repoOptions): array
    {
        $installFromGitScripts = $this->getInstallFromGitScripts($repoOptions);

        $preparePackagesScripts = [];

        foreach (array_keys($repoOptions) as $repoName) {
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
            ],
            "require" => [
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
                "install-from-git" => $installFromGitScripts,
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

        return $composer;
    }
}
