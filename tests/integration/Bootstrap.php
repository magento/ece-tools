<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Integration;

use Illuminate\Config\Repository;
use Magento\MagentoCloud\App\Container;
use Magento\MagentoCloud\Filesystem\Driver\File;
use Magento\MagentoCloud\Shell\ShellInterface;

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
     * @var ShellInterface
     */
    private $shell;

    /**
     * Bootstrap constructor.
     */
    public function __construct()
    {
        $this->file = new File();
        $this->shell = new Shell\Shell(
            $this->getSandboxDir()
        );
    }

    /**
     * @return Bootstrap
     */
    public static function getInstance(): Bootstrap
    {
        if (null === static::$instance) {
            static::$instance = new self();
        }

        return static::$instance;
    }

    /**
     * @param string $version
     * @param string $stability
     */
    public function run(string $version = '@stable', string $stability = 'stable')
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

        $this->shell->execute(sprintf(
            'composer create-project --no-dev --stability=%s --repository-url=%s %s %s "%s"',
            $stability,
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
     */
    public function createApplication(array $environment = []): Application
    {
        $environment = $this->getAllEnv($environment);

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
    private function getAllEnv(array $environment): Repository
    {
        return new Repository(array_replace(
            require $this->getConfigFile('environment.php'),
            $environment
        ));
    }

    /**
     * @param string $value
     * @param array $environment
     * @return array
     * @throws \Exception
     * @deprecated
     * @see \Magento\MagentoCloud\Util\UrlManager
     */
    public function getEnv(string $value, array $environment): array
    {
        return $this->getAllEnv($environment)->get($value);
    }

    /**
     * Destroy app.
     */
    public function destroy()
    {
        $this->file->clearDirectory(
            $this->getSandboxDir()
        );
    }

    /**
     * @return string
     * @deprecated
     * @see \Magento\MagentoCloud\Filesystem\SystemList
     */
    public function getSandboxDir(): string
    {
        return getenv('MAGENTO_ROOT') ?: ECE_BP . '/sandbox';
    }

    /**
     * @param string $file
     * @return string
     */
    private function getConfigFile(string $file): string
    {
        $configFile = ECE_BP . '/tests/integration/etc/' . $file;

        if ($this->file->isExists($configFile)) {
            return $configFile;
        }

        return $configFile . '.dist';
    }

    /**
     * Execute command.
     *
     * @param string $command
     * @return array
     * @deprecated
     * @see \Magento\MagentoCloud\Shell\ShellInterface
     */
    public function execute(string $command): array
    {
        exec($command, $output, $status);

        if ($status !== 0) {
            throw new \RuntimeException("Command $command returned code $status", $status);
        }

        return $output;
    }
}
