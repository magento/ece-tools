<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Integration;

use Magento\MagentoCloud\App\Bootstrap;
use Magento\MagentoCloud\Application;
use Magento\MagentoCloud\Filesystem\DirectoryList;

/**
 * @inheritdoc
 */
abstract class TestCase extends \PHPUnit\Framework\TestCase
{
    /**
     * @var array
     */
    private $env;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        $this->env = $_ENV;

        $sandboxDir = $this->deploySandbox();

        shell_exec(sprintf(
            "cp -rf %s %s",
            $this->getConfigFile('config.php'),
            $sandboxDir . '/app/etc/config.php'
        ));
    }

    /**
     * @inheritdoc
     */
    protected function tearDown()
    {
        $sandboxDir = $this->getSandboxDir();

        shell_exec(sprintf(
            "cd %s && php bin/magento setup:uninstall -n",
            $sandboxDir
        ));
        shell_exec(sprintf(
            "cd %s && rm -rf init",
            $sandboxDir
        ));

        $_ENV = $this->env;
    }

    /**
     * @return Application
     */
    protected function createApplication(array $environment): Application
    {
        $environment = array_replace_recursive(
            require $this->getConfigFile('environment.php'),
            $environment
        );

        $_ENV = array_replace($_ENV, [
            'MAGENTO_CLOUD_VARIABLES' => base64_encode(json_encode($environment['variables'])),
            'MAGENTO_CLOUD_RELATIONSHIPS' => base64_encode(json_encode($environment['relationships'])),
            'MAGENTO_CLOUD_ROUTES' => base64_encode(json_encode($environment['routes'])),
        ]);

        $server[Bootstrap::INIT_PARAM_DIRS_CONFIG] = [
            DirectoryList::MAGENTO_ROOT => [
                DirectoryList::PATH => '',
            ],
        ];

        return Bootstrap::create($this->getSandboxDir(), $server)->createApplication();
    }

    /**
     * @param string $file
     * @return string
     * @throws \Exception
     */
    private function getConfigFile(string $file): string
    {
        $configFile = __DIR__ . '/etc/' . $file;

        if (@file_exists($configFile)) {
            return $configFile;
        }

        if (@file_exists($configFile . '.dist')) {
            return $configFile . '.dist';
        }

        throw new \Exception('Config can not be found');
    }

    /**
     * @return string
     */
    private function deploySandbox(): string
    {
        $sandboxDir = $this->getSandboxDir();

        if (!is_dir($sandboxDir)) {
            mkdir($sandboxDir, 0777, true);

            $authFile = $this->getConfigFile('auth.json');
            $buildConfig = $this->getConfigFile('build_options.ini');

            $authMap = [
                'REPO_USERNAME' => 'http-basic.repo.magento.com.username',
                'REPO_PASSWORD' => 'http-basic.repo.magento.com.password',
                'CONNECT20_USERNAME' => 'http-basic.connect20-qa01.magedevteam.com.username',
                'CONNECT20_PASSWORD' => 'http-basic.connect20-qa01.magedevteam.com.password',
                'GH_TOKEN' => 'github-oauth.github.com'
            ];

            foreach ($authMap as $envName => $configPath) {
                echo $envName . ' was ' . !empty($_ENV[$envName]) ? 'found' : 'not found';

                if (empty($_ENV[$envName])) {
                    continue;
                }

                shell_exec(sprintf(
                    "cd %s && composer config %s %s",
                    $configPath,
                    $_ENV[$envName]
                ));
            }

            shell_exec(sprintf(
                "cd %s && git clone %s . ",
                $sandboxDir,
                'https://github.com/magento/magento-cloud-template'
            ));
            shell_exec(sprintf(
                "cd %s && git checkout -b %s",
                $sandboxDir,
                '2.2'
            ));
            shell_exec(sprintf(
                "cp -n %s %s",
                $authFile,
                $sandboxDir . '/auth.json'
            ));
            shell_exec(sprintf(
                "cp -rf %s %s",
                $buildConfig,
                $sandboxDir . '/build_options.ini'
            ));
            shell_exec(sprintf(
                "cd %s && composer install",
                $sandboxDir
            ));
        }

        return $sandboxDir;
    }

    /**
     * @return string
     */
    private function getSandboxDir(): string
    {
        $environmentFile = $this->getConfigFile('environment.php');

        return BP . '/tests/integration/tmp/sandbox-' . md5_file($environmentFile);
    }
}
