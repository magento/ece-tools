<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Integration;

use Illuminate\Config\Repository;
use Magento\MagentoCloud\App\Container;
use Magento\MagentoCloud\Application;
use Magento\MagentoCloud\Filesystem\Driver\File;

/**
 * Integration testing bootstrap.
 */
class Bootstrap
{
    /**
     * Distributive files.
     */
    const DIST_FILES = [
        'build_options.ini',
        '.magento.env.yaml',
    ];

    /**
     * @var Bootstrap
     */
    private static $instance;

    /**
     * @var File
     */
    private $file;

    /**
     * Bootstrap constructor.
     */
    public function __construct()
    {
        $this->file = new File();
    }

    /**
     * @return Bootstrap
     */
    public static function create(): Bootstrap
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

        if ($this->file->isExists($sandboxDir . '/composer.lock')) {
            return;
        }

        /**
         * Clean sandbox.
         */
        $this->destroy();

        if (!$this->file->isDirectory($sandboxDir)) {
            $this->file->createDirectory($sandboxDir);
        }

        $this->execute(sprintf(
            'composer create-project --repository-url=%s %s %s %s',
            getenv('MAGENTO_REPO') ?: 'https://repo.magento.com/',
            getenv('MAGENTO_PROJECT') ?: 'magento/project-enterprise-edition',
            $sandboxDir,
            $version
        ));

        foreach (self::DIST_FILES as $distFile) {
            $this->file->copy(
                $this->getConfigFile($distFile),
                $sandboxDir . '/' . $distFile
            );
        }
    }

    /**
     * @param array $environment
     * @return Application
     * @throws \Exception
     */
    public function createApplication(array $environment): Application
    {
        $environment = $this->mergeConfig($environment);
        $_ENV = array_replace($_ENV, (array)$environment);

        return new Application(
            new Container(ECE_BP, $this->getSandboxDir())
        );
    }

    /**
     * @param array $environment
     * @return Repository
     * @throws \Exception
     */
    public function mergeConfig(array $environment): Repository
    {
        return new Repository(array_replace(
            require $this->getConfigFile('environment.php'),
            $environment
        ));
    }

    /**
     * @throws \Magento\MagentoCloud\Filesystem\FileSystemException
     */
    public function destroy()
    {
        $this->file->clearDirectory(
            $this->getSandboxDir()
        );
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

        if ($this->file->isExists($configFile)) {
            return $configFile;
        }

        if ($this->file->isExists($configFile . '.dist')) {
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
    public function execute(string $command): string
    {
        exec($command, $output, $status);

        if ($status !== 0) {
            throw new \RuntimeException("Command $command returned code $status", $status);
        }

        return $output;
    }
}
