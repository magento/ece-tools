<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Command\Dev\UpdateComposer;

use Magento\MagentoCloud\Filesystem\DirectoryList;
use Magento\MagentoCloud\Filesystem\Driver\File;
use Magento\MagentoCloud\Filesystem\FileSystemException;
use Magento\MagentoCloud\Package\MagentoVersion;

/**
 * Generates composer.json data for installation from git.
 */
class ComposerGenerator
{
    /**
     * Types of supported Magento component packages
     */
    private const COMPONENT_PACKAGE_TYPES = [
        'magento2-module',
        'magento2-theme',
        'magento2-language',
        'magento2-library'
    ];

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
     * @var string
     */
    private $excludeRepoPathsPattern;

    /**
     * @param DirectoryList $directoryList
     * @param MagentoVersion $magentoVersion
     * @param File $file
     * @param string $excludeRepoPathsPattern
     */
    public function __construct(
        DirectoryList $directoryList,
        MagentoVersion $magentoVersion,
        File $file,
        $excludeRepoPathsPattern = '/^((?!test|Test|dev).)*$/'
    ) {
        $this->directoryList = $directoryList;
        $this->magentoVersion = $magentoVersion;
        $this->file = $file;
        $this->excludeRepoPathsPattern = $excludeRepoPathsPattern;
    }

    /**
     * Generates composer.json data for installation from git.
     *
     * @param array $repoOptions
     * @param array $installFromGitScripts
     * @return array
     * @throws FileSystemException
     * @codeCoverageIgnore
     */
    public function generate(array $repoOptions, array $installFromGitScripts): array
    {
        $composer = $this->getBaseComposer($installFromGitScripts);

        $rootComposerJsonPath = $this->directoryList->getMagentoRoot() . '/composer.json';
        if ($this->file->isExists($rootComposerJsonPath)) {
            $rootComposer = json_decode($this->file->fileGetContents($rootComposerJsonPath), true);
            $composer['require'] += $rootComposer['require'];
            $composer['repositories'] = array_merge($composer['repositories'], $rootComposer['repositories'] ?? []);
        } else {
            $composer['require'] += ['magento/ece-tools' => '2002.0.*'];
        }

        $preparePackagesScripts = [];
        foreach (array_keys($repoOptions) as $repoDir) {
            $baseRepoFolder = $this->directoryList->getMagentoRoot() . '/' . $repoDir;

            $dirComposerJson = $baseRepoFolder . '/composer.json';
            $isModuleRoot = false;
            if ($this->file->isExists($dirComposerJson)) {
                $dirPackageInfo = json_decode($this->file->fileGetContents($dirComposerJson), true);
                if ($this->isProjectPackage($dirPackageInfo)) {
                    $composer['require'] = array_merge($composer['require'], $dirPackageInfo['require']);
                } elseif ($this->isComponentPackage($dirPackageInfo)) {
                    $isModuleRoot = true;
                }
            }

            $repoPackages = $this->findPackages($baseRepoFolder);
            foreach ($repoPackages as $packageName => $packagePath) {
                $composer['repositories'][$packageName] = [
                    'type' => 'path',
                    'url' => $repoDir . '/' . $packagePath,
                    'options' => [
                        'symlink' => false,
                    ]
                ];
                $composer['require'][$packageName] = '*@dev';
            }
            if (!$isModuleRoot) {
                $excludeRepoStr = empty($repoPackages)
                    ? ''
                    : "--exclude='" . join("' --exclude='", $repoPackages) . "' ";
                $preparePackagesScripts[] = sprintf(
                    "rsync -azhm --stats $excludeRepoStr--exclude='dev/tests' --exclude='.git' " .
                    "--exclude='composer.json' --exclude='composer.lock' ./%s/ ./",
                    $repoDir
                );
            }
        }
        $composer['scripts']['prepare-packages'] = $preparePackagesScripts;
        $composer['scripts']['post-install-cmd'] = ['@prepare-packages'];

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
            if (!empty($gitOption['ref'])) {
                $script = sprintf(
                    'git clone %s "%s" && git --git-dir="%s/.git" --work-tree="%s" checkout %s',
                    $gitOption['repo'],
                    $repoName,
                    $repoName,
                    $repoName,
                    $gitOption['ref']
                );
            } else {
                $script = sprintf(
                    'git clone -b %s --single-branch --depth 1 %s %s',
                    $gitOption['branch'],
                    $gitOption['repo'],
                    $repoName
                );
            }
            $installFromGitScripts[] = $script;
        }

        return $installFromGitScripts;
    }

    /**
     * @param array $repoNames
     * @return array
     * @throws FileSystemException
     */
    public function getFrameworkPreparationScript(array $repoNames): array
    {
        $script = [];

        foreach ($repoNames as $repoName) {
            $path = $repoName . '/lib/internal/Magento/Framework';
            $absolutePath = $this->directoryList->getMagentoRoot() . '/' .$path;

            if ($this->file->isExists($absolutePath)) {
                foreach ($this->findPackages($absolutePath) as $package) {
                    if ($package) {
                        $script[] = 'mv ' . $path . '/' . $package . ' ' . $path . '-' . $package;
                    }
                }
            }
        }

        return $script;
    }

    /**
     * Returns base skeleton for composer.json.
     *
     * @param array $installFromGitScripts
     * @return array
     */
    private function getBaseComposer(array $installFromGitScripts): array
    {
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
            ],
            'require' => [
            ],
            'config' => [
                'use-include-path' => true,
                'allow-plugins' => [
                    'dealerdirect/phpcodesniffer-composer-installer' => true,
                    'laminas/laminas-dependency-plugin' => true,
                    'magento/*' => true
                ]
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
                'magento-deploy-ignore' => [
                    '*' => [
                        '/.gitignore'
                    ]
                ]
            ],
            'scripts' => [
                'install-from-git' => $installFromGitScripts,
                'pre-install-cmd' => [
                    '@install-from-git',
                ],
                'pre-update-cmd' => [
                    '@install-from-git',
                ],
            ],
        ];

        return $composer;
    }

    /**
     * Find Composer packages in the folder (recursively)
     *
     * @param string $path
     * @return array
     * @throws FileSystemException
     */
    private function findPackages(string $path)
    {
        $path = rtrim($path, '\\/');
        $pathLength = strlen($path . '/');

        $dirIterator = $this->file->getRecursiveFileIterator(
            $path,
            '/composer.json$/',
            $this->excludeRepoPathsPattern
        );
        $packages = [];
        foreach ($dirIterator as $currentFileInfo) {
            $packageInfo = json_decode($this->file->fileGetContents($currentFileInfo->getPathName()), true);
            if ($this->isComponentPackage($packageInfo)) {
                $packages[$packageInfo['name']] = substr($currentFileInfo->getPath(), $pathLength);
            }
        }

        return $packages;
    }

    /**
     * Check if provided package info belongs to a Magento component package
     *
     * @param array $packageInfo
     * @return bool
     */
    private function isComponentPackage(array $packageInfo): bool
    {
        return isset($packageInfo['type']) && in_array($packageInfo['type'], self::COMPONENT_PACKAGE_TYPES);
    }

    /**
     * Check if provided package info belongs to a Magento project package
     *
     * @param array $packageInfo
     * @return bool
     */
    private function isProjectPackage(array $packageInfo): bool
    {
        return isset($packageInfo['type']) && $packageInfo['type'] == 'project';
    }
}
