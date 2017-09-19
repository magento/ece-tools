<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Integration;

use Magento\MagentoCloud\App\Bootstrap;
use Magento\MagentoCloud\Application;

/**
 * @inheritdoc
 */
abstract class TestCase extends \PHPUnit\Framework\TestCase
{
    private $env;

    private $tmpDir;

    private $etcDir;

    /**
     * @var Application
     */
    private $application;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        $this->env = $_ENV;
        $this->etcDir = __DIR__ . '/etc';

        $configFile = $this->getConfigFile();

        $this->tmpDir = BP . '/tests/integration/tmp/sandbox-' . md5(sha1_file($configFile));
        $link = 'https://github.com/magento/magento-cloud';

        if (!is_dir($this->tmpDir)) {
            mkdir($this->tmpDir, 0777, true);

            shell_exec(sprintf(
                "cd %s && git clone %s . ",
                $this->tmpDir,
                $link
            ));
            shell_exec(sprintf(
                "cp -rf %s %s",
                $this->etcDir . '/auth.json.dist',
                $this->tmpDir . '/app/etc/auth.json'
            ));
            shell_exec(sprintf(
                "cd %s && composer install",
                $this->tmpDir
            ));
        }

        shell_exec(sprintf(
            "cp -rf %s %s",
            $this->etcDir . '/config.php.dist',
            $this->tmpDir . '/app/etc/config.php'
        ));

        $server[\Magento\MagentoCloud\App\Bootstrap::INIT_PARAM_DIRS_CONFIG] = [
            \Magento\MagentoCloud\Filesystem\DirectoryList::MAGENTO_ROOT => [
                \Magento\MagentoCloud\Filesystem\DirectoryList::PATH => '',
            ],
        ];

        $environment = require_once $configFile;

        $_ENV = array_merge($_ENV, [
            'MAGENTO_CLOUD_VARIABLES' => base64_encode(json_encode($environment['variables'])),
            'MAGENTO_CLOUD_RELATIONSHIPS' => base64_encode(json_encode($environment['relationships'])),
            'MAGENTO_CLOUD_ROUTES' => base64_encode(json_encode($environment['routes'])),
        ]);

        $this->application = Bootstrap::create($this->tmpDir, $server)
            ->createApplication();
    }

    /**
     * @inheritdoc
     */
    protected function tearDown()
    {
        shell_exec(sprintf(
            "cd %s && php bin/magento setup:uninstall -n",
            $this->tmpDir
        ));
        shell_exec(sprintf(
            "cd %s && rm -rf init",
            $this->tmpDir
        ));

        $_ENV = $this->env;
    }

    /**
     * @return Application
     */
    protected function getApplication(): Application
    {
        return $this->application;
    }

    /**
     * @return string
     * @throws \Exception
     */
    private function getConfigFile(): string
    {
        $configFile = $this->etcDir . '/environment.php';

        if (@file_exists($configFile)) {
            return $configFile;
        }

        if (@file_exists($configFile . '.dist')) {
            return $configFile . '.dist';
        }

        throw new \Exception('Config can not be found');
    }
}
