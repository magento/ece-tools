<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Test\Functional\Codeception;

use Codeception\Module;
use Magento\MagentoCloud\Test\Functional\Robo\Tasks as MagentoCloudTasks;
use Robo\LoadAllTasks as RoboTasks;
use Codeception\TestInterface;
use Codeception\Configuration;
use Robo\Collection\CollectionBuilder;
use Robo\Contract\BuilderAwareInterface;
use League\Container\ContainerAwareInterface;
use League\Container\ContainerAwareTrait;

/**
 * Module for running commands on Docker environment
 */
class Docker extends Module implements BuilderAwareInterface, ContainerAwareInterface
{
    use ContainerAwareTrait;

    use RoboTasks, MagentoCloudTasks {
        RoboTasks::getBuilder insteadof MagentoCloudTasks;
        RoboTasks::setBuilder insteadof MagentoCloudTasks;
        RoboTasks::collectionBuilder insteadof MagentoCloudTasks;
        RoboTasks::getBuiltTask insteadof MagentoCloudTasks;
        RoboTasks::task insteadof MagentoCloudTasks;
    }

    const BUILD_CONTAINER = 'build';
    const DEPLOY_CONTAINER = 'deploy';

    /**
     * @var array
     */
    protected $config = [
        'db_host' => '',
        'db_port' => '3306',
        'db_username' => '',
        'db_password' => '',
        'db_path' => '',
        'repo_url' => '',
        'repo_branch' => '',
        'system_ece_tools_dir' => '',
        'system_magento_dir' => '',
        'env_base_url' => '',
        'env_secure_base_url' => '',
        'volumes' => []
    ];

    /**
     * @inheritdoc
     */
    public function _initialize()
    {
        $container = require Configuration::projectDir() . 'tests/functional/bootstrap.php';
        $builder = CollectionBuilder::create($container, $this);

        $this->setContainer($container);
        $this->setBuilder($builder);
    }

    /**
     * @inheritdoc
     */
    public function _before(TestInterface $test)
    {
        $this->taskEnvUp($this->_getConfig('volumes'))
            ->run()
            ->stopOnFail();
    }

    /**
     * @inheritdoc
     */
    public function _after(TestInterface $test)
    {
        $this->taskEnvDown()
            ->run()
            ->stopOnFail();
    }

    /**
     * Clones magento cloud template from git
     *
     * @param string|null $version
     * @return bool
     * @throws \Robo\Exception\TaskException
     */
    public function cloneTemplate(string $version = null): bool
    {
        $gitTask = $this->taskGitStack()
            ->exec('git init')
            ->exec(sprintf('git remote add origin %s', $this->_getConfig('repo_url')))
            ->exec('git fetch')
            ->checkout($version ?: $this->_getConfig('repo_branch'));

        return $this->taskBash(self::BUILD_CONTAINER)
            ->exec($gitTask)
            ->run()
            ->wasSuccessful();
    }

    /**
     * Runs composer require command
     *
     * @param string $version
     * @return bool
     * @throws \Robo\Exception\TaskException
     */
    public function composerRequireMagentoCloud(string $version): bool
    {
        $composerRequireTask = $this->taskComposerRequire('composer')
            ->dependency('magento/magento-cloud-metapackage', $version)
            ->workingDir($this->_getConfig('system_magento_dir'))
            ->noInteraction()
            ->option('--no-update');
        $composerUpdateTask = $this->taskComposerUpdate('composer');

        return $this->taskBash(self::BUILD_CONTAINER)
            ->exec($composerRequireTask->getCommand() . ' && ' . $composerUpdateTask->getCommand())
            ->run()
            ->wasSuccessful();
    }

    /**
     * Runs composer install command
     *
     * @return bool
     * @throws \Robo\Exception\TaskException
     */
    public function composerInstall(): bool
    {
        $composerTask = $this->taskComposerInstall('composer')
            ->noDev()
            ->noInteraction()
            ->workingDir($this->_getConfig('system_magento_dir'));
        return $this->taskBash(self::BUILD_CONTAINER)
            ->exec($composerTask)
            ->run()
            ->wasSuccessful();
    }

    /**
     * Cleans directories
     *
     * @param string|array $path
     * @param string $container
     * @return bool
     * @throws \Robo\Exception\TaskException
     */
    public function cleanDirectories($path, string $container = self::BUILD_CONTAINER): bool
    {
        $magentoRoot = $this->_getConfig('system_magento_dir');

        if (is_array($path)) {
            $path = array_map(
                function($val) use ($magentoRoot) { return $magentoRoot . $val; },
                $path
            );
            $pathsToCleanup = implode(' ', $path);
        } else {
            $pathsToCleanup = $magentoRoot . $path;
        }

        return $this->taskBash($container)
            ->exec('rm -rf ' . $pathsToCleanup)
            ->run()
            ->wasSuccessful();
    }

    /**
     * Downloads files from Docker container
     *
     * @param string $source
     * @param string $destination
     * @param string $container
     * @return bool
     */
    public function downloadFromContainer(string $source , string $destination, string $container): bool
    {
        return $this->taskCopyFromDocker($this->_getConfig('system_magento_dir') . $source, $destination, $container)
            ->run()
            ->wasSuccessful();
    }

    /**
     * Returns file contents
     *
     * @param string $source
     * @param string $container
     * @return false|string
     */
    public function grabFileContent(string $source, string $container = self::DEPLOY_CONTAINER)
    {
        $tmpFile = tempnam(sys_get_temp_dir(), md5($source));
        $this->downloadFromContainer($source, $tmpFile, $container);
        return file_get_contents($tmpFile);
    }

    /**
     * Runs ece-tools command on Docker container
     *
     * @param string $command
     * @param string $container
     * @param array $variables
     * @return bool
     * @throws \Robo\Exception\TaskException
     */
    public function runEceToolsCommand(string $command, string $container, array $variables = []): bool
    {
        $variables = array_replace($this->getDefaultVariables(), $variables);

        foreach ($variables as $varName => $varValue) {
            $variables[$varName] = base64_encode(json_encode($varValue));
        }

        return $this->taskBash($container)
            ->envVars($variables)
            ->exec(sprintf('%s/bin/ece-tools %s', $this->_getConfig('system_ece_tools_dir'), $command))
            ->run()
            ->wasSuccessful();
    }

    /**
     * Returns default environment variables
     *
     * @return array
     */
    private function getDefaultVariables(): array
    {
        return [
            'MAGENTO_CLOUD_RELATIONSHIPS' => [
                'database' => [
                    0 => [
                        'host' => $this->_getConfig('db_host'),
                        'path' => $this->_getConfig('db_path'),
                        'password' => $this->_getConfig('db_password'),
                        'username' => $this->_getConfig('db_username'),
                        'port' => $this->_getConfig('db_port'),
                    ],
                ],
            ],
            'MAGENTO_CLOUD_ROUTES' => [
                $this->_getConfig('env_base_url') => [
                    'type' => 'upstream',
                    'original_url' => 'http://{default}',
                ],
                $this->_getConfig('env_secure_base_url') => [
                    'type' => 'upstream',
                    'original_url' => 'https://{default}',
                ]
            ],
            'MAGENTO_CLOUD_VARIABLES' => [
                'ADMIN_EMAIL' => 'admin@example.com',
            ],
        ];
    }
}
