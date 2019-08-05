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
    const REPO_TYPE_SINGLE_PACKAGE = 'single-package';

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
     * @codeCoverageIgnore
     */
    public function generate(array $repoOptions): array
    {
        $composer = $this->getBaseComposer($repoOptions);

        $rootComposerJsonPath = $this->directoryList->getMagentoRoot() . '/composer.json';
        if ($this->file->isExists($rootComposerJsonPath)) {
            $rootComposer = json_decode($this->file->fileGetContents($rootComposerJsonPath), true);
            $composer['require'] += $rootComposer['require'];
            $composer['repositories'] = array_merge($composer['repositories'], $rootComposer['repositories'] ?? []);
        } else {
            $composer['require'] += ['magento/ece-tools' => '2002.0.*'];
        }

        foreach (array_keys($repoOptions) as $repoName) {
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

        foreach (array_keys($composer['require']) as $packageName) {
            if (preg_match('/magento\/framework|magento\/module/', $packageName)) {
                $composer['require'][$packageName] = '*';
            }
        }

        $composer = $this->addModules($repoOptions, $composer);

        return $composer;
    }

    /**
     * @param array $repoOptions
     * @return array
     */
    public function getInstallFromGitScripts(array $repoOptions): array
    {
        $installFromGitScripts = ['php -r"@mkdir(__DIR__ . \'/app/etc\', 0777, true);"'];
        $installFromGitScripts[] = 'rm -rf ' . implode(' ', array_keys($repoOptions));

        foreach ($repoOptions as $repoName => $gitOption) {
            $gitCloneCommand = 'git clone -b %s --single-branch --depth 1 %s %s';

            $installFromGitScripts[] = sprintf(
                $gitCloneCommand,
                $gitOption['branch'],
                $gitOption['repo'],
                $repoName
            );
        }

        return $installFromGitScripts;
    }

    /**
     * Returns base skeleton for composer.json.
     *
     * @param array $repoOptions
     * @return array
     */
    private function getBaseComposer(array $repoOptions): array
    {
        $installFromGitScripts = $this->getInstallFromGitScripts($repoOptions);

        $preparePackagesScripts = [];

        foreach ($repoOptions as $repoName => $gitOption) {
            if ($this->isSinglePackage($gitOption)) {
                continue;
            }

            $preparePackagesScripts[] = sprintf(
                "rsync -azh --stats --exclude='app/code/Magento/' --exclude='app/i18n/' --exclude='app/design/' "
                . "--exclude='dev/tests' --exclude='lib/internal/Magento' --exclude='.git' ./%s/ ./",
                $repoName
            );
        }

        $composer = [
            'name' => 'magento/cloud-dev',
            'description' => 'eCommerce Platform for Growth',
            'type' => 'project',
            'version' => $this->magentoVersion->getVersion(),
            'license' => [
                'OSL-3.0',
            ],
            'bin' => [
                'ce/bin/magento',
            ],
            'repositories' => [
                'magento/framework' => [
                    'type' => 'path',
                    'url' => './ce/lib/internal/Magento/Framework/',
                    'transport-options' => [
                        'symlink' => false,
                    ],
                    'options' => [
                        'symlink' => false,
                    ],
                ],
            ],
            'require' => [
            ],
            'config' => [
                'use-include-path' => true,
            ],
            'autoload' => [
                'psr-4' => [
                    'Magento\\Setup\\' => 'setup/src/Magento/Setup/',
                    'Zend\\Mvc\\Controller\\' => 'setup/src/Zend/Mvc/Controller/'
                ],
            ],
            'minimum-stability' => 'dev',
            'prefer-stable' => true,
            'extra' => [
                'magento-force' => 'override',
                'magento-deploystrategy' => 'copy',
            ],
            'scripts' => [
                'install-from-git' => $installFromGitScripts,
                'prepare-packages' => $preparePackagesScripts,
                'pre-install-cmd' => [
                    '@install-from-git',
                ],
                'pre-update-cmd' => [
                    '@install-from-git',
                ],
                'post-install-cmd' => [
                    '@prepare-packages',
                ],
            ],
        ];

        return $composer;
    }

    /**
     * Adds modules and repositories to composer.json.
     *
     * @param array $repoOptions
     * @param array $composer
     * @return array
     * @codeCoverageIgnore
     */
    private function addModules(array $repoOptions, array $composer): array
    {
        $add = function ($dir, $version = null) use (&$composer) {
            if (!$this->file->isExists($dir . '/composer.json')) {
                return 0;
            }

            $dirComposer = json_decode($this->file->fileGetContents($dir . '/composer.json'), true);
            $composer['repositories'][$dirComposer['name']] = [
                'type' => 'path',
                'url' => ltrim(str_replace($this->directoryList->getMagentoRoot(), '', $dir), '/'),
                'options' => [
                    'symlink' => false,
                ],
            ];
            $composer['require'][$dirComposer['name']] = $version ?? $dirComposer['version'] ?? '*';
        };

        foreach ($repoOptions as $repoName => $gitOption) {
            $baseRepoFolder = $this->directoryList->getMagentoRoot() . '/' . $repoName;
            if ($this->isSinglePackage($gitOption)) {
                $add($baseRepoFolder, '*');
                continue;
            }

            foreach (glob($baseRepoFolder . '/app/code/Magento/*') as $dir) {
                $add($dir);
            }
            foreach (glob($baseRepoFolder . '/app/design/*/Magento/*/') as $dir) {
                $add($dir);
            }
            foreach (glob($baseRepoFolder . '/app/design/*/Magento/*/') as $dir) {
                $add($dir);
            }
            if ($this->file->isDirectory($baseRepoFolder . '/lib/internal/Magento/Framework/')) {
                foreach (glob($baseRepoFolder . '/lib/internal/Magento/Framework/*') as $dir) {
                    $add($dir);
                }
            }
        }

        return $composer;
    }

    /**
     * @param array $repoOptions
     * @return bool
     */
    private function isSinglePackage(array $repoOptions): bool
    {
        return isset($repoOptions['type']) && $repoOptions['type'] == self::REPO_TYPE_SINGLE_PACKAGE;
    }
}
