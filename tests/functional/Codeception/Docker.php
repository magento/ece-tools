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
use Robo\Result;
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
        'volumes' => [],
        'printOutput' => false,
    ];

    /**
     * @var string
     */
    protected $output = '';

    /**
     * @var array
     */
    protected $services = [];

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
     * Stops Docker env
     *
     * @return bool
     */
    public function stopEnvironment(): bool
    {
        return $this->taskEnvDown()
            ->printOutput($this->_getConfig('printOutput'))
            ->interactive(false)
            ->run()
            ->stopOnFail()
            ->wasSuccessful();
    }

    /**
     * Start Docker env
     *
     * @return bool
     */
    public function startEnvironment(): bool
    {
        return $this->taskEnvUp()
            ->printOutput($this->_getConfig('printOutput'))
            ->interactive(false)
            ->run()
            ->stopOnFail()
            ->wasSuccessful();
    }

    /**
     * Removes docker-compose.yml
     *
     * @return bool
     */
    public function removeDockerCompose(): bool
    {
        return $this->taskRemoveDockerCompose()
            ->printOutput($this->_getConfig('printOutput'))
            ->interactive(false)
            ->run()
            ->stopOnFail()
            ->wasSuccessful();
    }

    /**
     * Generates docker-compose.yml
     *
     * @param array $services
     * @return bool
     */
    public function generateDockerCompose(array $services = []): bool
    {
        $this->services = $services;
        /** @var Result $result */
        $result = $this->taskGenerateDockerCompose($services)
            ->printOutput($this->_getConfig('printOutput'))
            ->interactive(false)
            ->run()
            ->stopOnFail();

        $this->output = $result->getMessage();

        return $result->wasSuccessful();
    }

    /**
     * Clean up Docker env
     *
     * @return bool
     */
    public function cleanUpEnvironment(): bool
    {
        /** @var Result $result */
        $result = $this->taskEnvCleanUp($this->_getConfig('volumes'))
            ->printOutput($this->_getConfig('printOutput'))
            ->interactive(false)
            ->run()
            ->stopOnFail();

        $this->output = $result->getMessage();

        return $result->wasSuccessful();
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

        /** @var Result $result */
        $result = $this->taskBash(self::BUILD_CONTAINER)
            ->printOutput($this->_getConfig('printOutput'))
            ->interactive(false)
            ->exec($gitTask)
            ->run();

        $this->output = $result->getMessage();

        return $result->wasSuccessful();
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
        /**
         * This is temporary fix for MAGECLOUD-3714
         */
        $composerRequireCarbonTask = $this->taskComposerRequire('composer')
            ->dependency('nesbot/carbon', '<1.38||^2.0')
            ->workingDir($this->_getConfig('system_magento_dir'))
            ->noInteraction()
            ->option('--no-update');
        $composerUpdateTask = $this->taskComposerUpdate('composer');

        $tasks = [
            $composerRequireTask->getCommand(),
            $composerRequireCarbonTask->getCommand(),
            $composerUpdateTask->getCommand()
        ];

        /** @var Result $result */
        $result = $this->taskBash(self::BUILD_CONTAINER)
            ->printOutput($this->_getConfig('printOutput'))
            ->interactive(false)
            ->exec(implode(' && ', $tasks))
            ->run();

        $this->output = $result->getMessage();

        return $result->wasSuccessful();
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

        /** @var Result $result */
        $result = $this->taskBash(self::BUILD_CONTAINER)
            ->printOutput($this->_getConfig('printOutput'))
            ->interactive(false)
            ->exec($composerTask)
            ->run();

        $this->output = $result->getMessage();

        return $result->wasSuccessful();
    }

    /**
     * Add local checkout of ECE Tools to composer repositories.
     *
     * @return bool
     * @throws \Robo\Exception\TaskException
     */
    public function addEceComposerRepo(): bool
    {
        $eceToolsVersion = '2002.0.999';
        $commands = [
            $this->taskComposerConfig('composer')
                ->set('repositories.ece-tools', addslashes(json_encode(
                    [
                        'type' => 'package',
                        'package' => [
                            'name' => 'magento/ece-tools',
                            'version' => $eceToolsVersion,
                            'source' => [
                                'type' => 'git',
                                'url' => $this->_getConfig('system_ece_tools_dir'),
                                'reference' => exec('git rev-parse HEAD'),
                            ],
                        ],
                    ],
                    JSON_UNESCAPED_SLASHES
                )))->noInteraction()
                ->getCommand(),
            $this->taskComposerRequire('composer')
                ->dependency('magento/ece-tools', $eceToolsVersion)
                ->noInteraction()
                ->getCommand()
        ];

        $customDeps = [
            'mcp' => [
                'name' => 'magento/magento-cloud-patches',
                'repo' => [
                    'type' => 'vcs',
                    'url' => 'git@github.com:magento/magento-cloud-patches.git'
                ]
            ]
        ];
        $config = json_decode(
            file_get_contents( __DIR__ . '/../../../composer.json'),
            true
        );

        foreach ($customDeps as $depName => $extra) {
            if (isset($config['require'][$extra['name']])) {
                $commands[] = $this->taskComposerConfig('composer')
                    ->set('repositories.' . $depName, addslashes(json_encode($extra['repo'], JSON_UNESCAPED_SLASHES)))
                    ->noInteraction()
                    ->getCommand();
                $commands[] = $this->taskComposerRequire('composer')
                    ->dependency($extra['name'], $config['require'][$extra['name']])
                    ->noInteraction()
                    ->getCommand();
            }
        }

        $result = $this->taskBash(self::BUILD_CONTAINER)
            ->workingDir($this->_getConfig('system_magento_dir'))
            ->printOutput($this->_getConfig('printOutput'))
            ->interactive(false)
            ->exec(implode(' && ', $commands))
            ->run();

        $this->output = $result->getMessage();

        return $result->wasSuccessful();
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
                function ($val) use ($magentoRoot) {
                    return $magentoRoot . $val;
                },
                $path
            );
            $pathsToCleanup = implode(' ', $path);
        } else {
            $pathsToCleanup = $magentoRoot . $path;
        }

        /** @var Result $result */
        $result = $this->taskBash($container)
            ->printOutput($this->_getConfig('printOutput'))
            ->interactive(false)
            ->exec('rm -rf ' . $pathsToCleanup)
            ->run();

        $this->output = $result->getMessage();

        return $result->wasSuccessful();
    }

    /**
     * Downloads files from Docker container
     *
     * @param string $source
     * @param string $destination
     * @param string $container
     * @return bool
     */
    public function downloadFromContainer(string $source, string $destination, string $container): bool
    {
        /** @var Result $result */
        $result = $this->taskCopyFromDocker($container)
            ->printOutput($this->_getConfig('printOutput'))
            ->interactive(false)
            ->source($this->_getConfig('system_magento_dir') . $source)
            ->destination($destination)
            ->run();

        $this->output = $result->getMessage();

        return $result->wasSuccessful();
    }

    /**
     * Creates folder on Docker
     *
     * @param string $path
     * @param string $container
     * @return bool
     * @throws \Robo\Exception\TaskException
     */
    public function createDirectory(string $path, string $container): bool
    {
        /** @var Result $result */
        $result = $this->taskBash($container)
            ->printOutput($this->_getConfig('printOutput'))
            ->interactive(false)
            ->exec(sprintf('mkdir -p %s', $this->_getConfig('system_magento_dir') . $path))
            ->run();

        $this->output = $result->getMessage();

        return $result->wasSuccessful();
    }

    /**
     * Uploads files to Docker container
     *
     * Relative paths for $source will be expanded from Codeception's data directory.
     *
     * @param string $source
     * @param string $destination
     * @param string $container
     * @return bool
     */
    public function uploadToContainer(string $source, string $destination, string $container): bool
    {
        if (substr($source, 0, 1) != '/') {
            $source = Configuration::dataDir() . $source;
        }

        /** @var Result $result */
        $result = $this->taskCopyToDocker($container)
            ->printOutput($this->_getConfig('printOutput'))
            ->interactive(false)
            ->source($source)
            ->destination($this->_getConfig('system_magento_dir') . $destination)
            ->run();

        $this->output = $result->getMessage();

        return $result->wasSuccessful();
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
     * @param array $cloudVariables
     * @param array $rawVariables
     * @return bool
     * @throws \Robo\Exception\TaskException
     */
    public function runEceToolsCommand(
        string $command,
        string $container,
        array $cloudVariables = [],
        array $rawVariables = []
    ): bool {
        /** @var Result $result */
        $result = $this->taskBash($container)
            ->printOutput($this->_getConfig('printOutput'))
            ->interactive(false)
            ->envVars($this->prepareVariables($cloudVariables))
            ->envVars($rawVariables)
            ->exec(sprintf('php %s/bin/ece-tools %s', $this->_getConfig('system_ece_tools_dir'), $command))
            ->run();

        $this->output = $result->getMessage();

        return $result->wasSuccessful();
    }

    /**
     * Checks that output contains $text
     *
     * @param string $text
     */
    public function seeInOutput(string $text)
    {
        \PHPUnit\Framework\Assert::assertContains($text, $this->output);
    }

    /**
     * Runs bin/magento command on Docker container
     *
     * @param string $command
     * @param string $container
     * @param array $cloudVariables
     * @param array $rawVariables
     * @return bool
     * @throws \Robo\Exception\TaskException
     */
    public function runBinMagentoCommand(
        string $command,
        string $container,
        array $cloudVariables = [],
        array $rawVariables = []
    ): bool {
        /** @var Result $result */
        $result = $this->taskBash($container)
            ->printOutput($this->_getConfig('printOutput'))
            ->interactive(false)
            ->envVars($this->prepareVariables($cloudVariables))
            ->envVars($rawVariables)
            ->exec(sprintf('php %s/bin/magento %s', $this->_getConfig('system_magento_dir'), $command))
            ->run();

        $this->output = $result->getMessage();

        return $result->wasSuccessful();
    }

    /**
     * Returns DB credential
     *
     * @return array
     */
    public function getDbCredential(): array
    {
        return [
            'host' => $this->_getConfig('db_host'),
            'path' => $this->_getConfig('db_path'),
            'password' => $this->_getConfig('db_password'),
            'username' => $this->_getConfig('db_username'),
            'port' => $this->_getConfig('db_port'),
        ];
    }

    /**
     * Prepares environment variables
     *
     * @param array $variables
     * @return array
     */
    private function prepareVariables(array $variables): array
    {
        $variables = array_replace($this->getDefaultVariables(), $variables);

        foreach ($variables as $varName => $varValue) {
            $variables[$varName] = base64_encode(json_encode($varValue));
        }

        return $variables;
    }

    /**
     * Returns default environment variables
     *
     * @return array
     */
    private function getDefaultVariables(): array
    {
        $variables = [
            'MAGENTO_CLOUD_RELATIONSHIPS' => [
                'database' => [
                    $this->getDbCredential(),
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

        if (isset($this->services['es'])) {
            $variables['MAGENTO_CLOUD_RELATIONSHIPS']['elasticsearch'] = [
                [
                    'host' => 'elasticsearch',
                    'port' => '9200',
                ],
            ];
        }

        if (isset($this->services['redis'])) {
            $variables['MAGENTO_CLOUD_RELATIONSHIPS']['redis'] = [
                [
                    'host' => 'redis',
                    'port' => '6379',
                ],
            ];
        }

        if (isset($this->services['rmq'])) {
            $variables['MAGENTO_CLOUD_RELATIONSHIPS']['rabbitmq'] = [
                [
                    'host' => 'rabbitmq',
                    'port' => '5672',
                    'username' => 'guest',
                    'password' => 'guest',
                ],
            ];
        }

        return $variables;
    }
}
