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

        $buildFile = $this->getConfigFile('build_options.ini');
        $envConfig = $this->mergeConfig([]);

        if (!is_dir($sandboxDir)) {
            mkdir($sandboxDir, 0777, true);
        }

        $this->execute(sprintf(
            'composer create-project --no-dev --repository-url=%s %s %s %s',
            $envConfig->get('deploy.repo'),
            $envConfig->get('deploy.name'),
            $sandboxDir,
            $version
        ));

        /**
         * Copying build options.
         */
        $this->execute(sprintf(
            'cp -f %s %s',
            $buildFile,
            $sandboxDir . '/build_options.ini'
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
            'rm -rf %s',
            $this->getSandboxDir()
        ));
    }

    /**
     * @return string
     */
    public function getSandboxDir(): string
    {
        return ECE_BP . '/sandbox';
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

        $environment = getenv('environment') ?? '';

        if (@file_exists($configFile . '.dist')) {
            return $configFile . $environment . '.dist';
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
