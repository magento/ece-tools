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
     * @throws \Exception
     */
    private function deploySandbox(): string
    {
        $sandboxDir = $this->getSandboxDir();

        if (is_dir($sandboxDir)) {
            return $sandboxDir;
        }

        mkdir($sandboxDir, 0777, true);

        $authFile = $this->getConfigFile('auth.json');
        $buildFile = $this->getConfigFile('build_options.ini');
        $deployConfig = (require $this->getConfigFile('environment.php'))['deploy'];

        if ($deployConfig['type'] === 'git') {
            shell_exec(sprintf(
                "cd %s && git clone %s . ",
                $sandboxDir,
                $deployConfig['types']['git']['repo']
            ));
            shell_exec(sprintf(
                "cd %s && git checkout -b %s",
                $sandboxDir,
                $deployConfig['types']['git']['version']
            ));
        } elseif ($deployConfig['type'] === 'project') {
            shell_exec(sprintf(
                "cd %s && composer create-project --no-dev --repository-url=%s %s . %s",
                $sandboxDir,
                $deployConfig['types']['project']['repo'],
                $deployConfig['types']['project']['name'],
                $deployConfig['types']['project']['version']
            ));
        } else {
            throw new \Exception('Wrong deploy type');
        }

        shell_exec(sprintf(
            "cp -n %s %s",
            $authFile,
            $sandboxDir . '/auth.json'
        ));
        shell_exec(sprintf(
            "cp -rf %s %s",
            $buildFile,
            $sandboxDir . '/build_options.ini'
        ));
        shell_exec(sprintf(
            "cd %s && composer install",
            $sandboxDir
        ));

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
