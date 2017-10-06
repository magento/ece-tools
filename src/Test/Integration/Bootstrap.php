<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Integration;

use Magento\MagentoCloud\Application;
use Magento\MagentoCloud\Filesystem\DirectoryList;

/**
 * Integration testing bootstrap.
 */
class Bootstrap
{
    const DEPLOY_TYPE = 'type';
    const DEPLOY_TYPE_GIT = 'git';
    const DEPLOY_TYPE_PROJECT = 'project';

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
     * @throws \Exception
     */
    public function run()
    {
        $sandboxDir = $this->getSandboxDir();

        if (file_exists($sandboxDir . '/composer.lock')) {
            return;
        }

        $buildFile = $this->getConfigFile('build_options.ini');
        $deployConfig = (require $this->getConfigFile('environment.php'))['deploy'];
        $deployType = getenv('DEPLOY_TYPE')
            ? getenv('DEPLOY_TYPE')
            : $deployConfig[static::DEPLOY_TYPE];

        if (!$deployType || !array_key_exists($deployType, $deployConfig['types'])) {
            throw new \Exception(
                sprintf('Deploy type %s was not configured', $deployType)
            );
        }

        if (!is_dir($sandboxDir)) {
            mkdir($sandboxDir, 0777, true);
        }

        switch ($deployConfig[static::DEPLOY_TYPE]) {
            case static::DEPLOY_TYPE_GIT:
                $gitConfig = $deployConfig['types'][static::DEPLOY_TYPE_GIT];

                $this->execute(
                    sprintf(
                        'cd %s && git clone %s .',
                        $sandboxDir,
                        $gitConfig['repo']
                    )
                );
                $this->execute(
                    sprintf(
                        'cd %s && git checkout -b %s',
                        $sandboxDir,
                        $gitConfig['version']
                    )
                );
                break;
            case static::DEPLOY_TYPE_PROJECT:
                $projectConfig = $deployConfig['types'][static::DEPLOY_TYPE_PROJECT];

                $this->execute(
                    sprintf(
                        'composer create-project --no-dev --repository-url=%s %s %s %s',
                        $projectConfig['repo'],
                        $projectConfig['name'],
                        $sandboxDir,
                        $projectConfig['version']
                    )
                );
                break;
            default:
                throw new \Exception('Wrong deploy type');
        }

        $this->execute(
            sprintf(
                'cp -f %s %s',
                $buildFile,
                $sandboxDir . '/build_options.ini'
            )
        );
        $this->execute(
            sprintf(
                'composer install -n -d %s',
                $sandboxDir
            )
        );
    }

    /**
     * @param array $environment
     * @return Application
     */
    public function createApplication(array $environment): Application
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

        $server[\Magento\MagentoCloud\App\Bootstrap::INIT_PARAM_DIRS_CONFIG] = [
            DirectoryList::MAGENTO_ROOT => [
                DirectoryList::PATH => '',
            ],
        ];

        return \Magento\MagentoCloud\App\Bootstrap::create($this->getSandboxDir(), $server)
            ->createApplication();
    }

    /**
     * @return string
     */
    public function getSandboxDir(): string
    {
        $environmentFile = $this->getConfigFile('environment.php');
        $sandboxKey = getenv('SANDBOX_KEY')
            ? getenv('SANDBOX_KEY')
            : md5_file($environmentFile);

        return ECE_BP . '/tests/integration/tmp/sandbox-' . $sandboxKey;
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

        throw new \Exception(
            sprintf('Config file %s can not be found', $file)
        );
    }

    /**
     * @param string $command
     * @return string
     */
    private function execute(string $command)
    {
        exec($command, $output, $status);

        if ($status !== 0) {
            throw new \RuntimeException("Command $command returned code $status", $status);
        }

        return $output;
    }
}
