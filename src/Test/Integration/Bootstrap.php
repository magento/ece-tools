<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Integration;

use Illuminate\Config\Repository;
use Magento\MagentoCloud\App\Container;
use Magento\MagentoCloud\Application;

/**
 * Integration testing bootstrap.
 */
class Bootstrap
{
    /**
     * @var Bootstrap
     */
    private static $instance;

    /**
     * @return Bootstrap
     */
    public static function create()
    {
        if (null === static::$instance) {
            static::$instance = new self();
        }

        return static::$instance;
    }

    /**
     * @param string $version
     * @throws \Exception
     */
    public function run($version = '@stable')
    {
        $sandboxDir = $this->getSandboxDir();

        if (file_exists($sandboxDir . '/composer.lock')) {
            return;
        }

        /**
         * Clean sandbox.
         */
        $this->destroy();

        if (!is_dir($sandboxDir)) {
            mkdir($sandboxDir, 0777, true);
        }

        $this->execute(sprintf(
            'composer create-project --repository-url=%s %s %s %s',
            'https://repo.magento.com/',
            'magento/project-enterprise-edition',
            $sandboxDir,
            $version
        ));

        /**
         * Copying build options.
         */
        $this->execute(sprintf(
            'cp -f %s %s',
            $this->getConfigFile('build_options.ini'),
            $sandboxDir . '/build_options.ini'
        ));

        /**
         * Copying env file.
         */
        $this->execute(sprintf(
            'cp -f %s %s',
            $this->getConfigFile('.magento.env.yaml'),
            $sandboxDir . '/.magento.env.yaml'
        ));
    }

    /**
     * @param array $environment
     * @return Application
     */
    public function createApplication(array $environment): Application
    {
        $environment = $this->mergeConfig($environment);

        $_ENV = array_replace($_ENV, [
            'MAGENTO_CLOUD_VARIABLES' => base64_encode(json_encode(
                $environment->get('variables', [])
            )),
            'MAGENTO_CLOUD_RELATIONSHIPS' => base64_encode(json_encode(
                $environment->get('relationships', [])
            )),
            'MAGENTO_CLOUD_ROUTES' => base64_encode(json_encode(
                $environment->get('routes', [])
            )),
            'MAGENTO_CLOUD_APPLICATION' => base64_encode(json_encode(
                []
            )),
        ]);

        $container = new Container(ECE_BP, $this->getSandboxDir());

        return new Application($container);
    }

    /**
     * @param array $environment
     * @return Repository
     */
    public function mergeConfig(array $environment): Repository
    {
        return new Repository(array_replace(
            require $this->getConfigFile('environment.php'),
            $environment
        ));
    }

    /**
     * Removes application directory.
     */
    public function destroy()
    {
        $this->execute(sprintf(
            'rm -rf %s/*',
            $this->getSandboxDir()
        ));
        $this->execute(sprintf(
            'find %s -mindepth 1 -name \'.*\' -delete',
            $this->getSandboxDir()
        ));
    }

    /**
     * @return string
     */
    public function getSandboxDir(): string
    {
        return getenv('MAGENTO_ROOT') ?: ECE_BP . '/sandbox';
    }

    /**
     * @param string $file
     * @return string
     * @throws \Exception
     */
    public function getConfigFile(string $file): string
    {
        $configFile = ECE_BP . '/tests/integration/etc/' . $file;

        if (@file_exists($configFile)) {
            return $configFile;
        }

        if (@file_exists($configFile . '.dist')) {
            return $configFile . '.dist';
        }

        throw new \Exception(sprintf(
            'Config file %s can not be found',
            $file
        ));
    }

    /**
     * @param string $command
     * @return string
     */
    public function execute(string $command)
    {
        exec($command, $output, $status);

        if ($status !== 0) {
            throw new \RuntimeException("Command $command returned code $status", $status);
        }

        return $output;
    }
}
