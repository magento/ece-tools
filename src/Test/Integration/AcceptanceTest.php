<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Integration;

use Magento\MagentoCloud\Command\Build;
use Magento\MagentoCloud\Command\Deploy;
use Magento\MagentoCloud\Config\Environment;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * @inheritdoc
 */
class AcceptanceTest extends TestCase
{
    /**
     * @var Bootstrap
     */
    private $bootstrap;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        $this->bootstrap = Bootstrap::create();

        shell_exec(sprintf(
            "cd %s && rm -rf init",
            $this->bootstrap->getSandboxDir()
        ));
    }

    /**
     * @inheritdoc
     */
    protected function tearDown()
    {
        shell_exec(sprintf(
            'cd %s && php bin/magento setup:uninstall -n',
            $this->bootstrap->getSandboxDir()
        ));
    }

    /**
     * @param array $environment
     * @dataProvider defaultDataProvider
     */
    public function testDefault(array $environment)
    {
        $application = $this->bootstrap->createApplication($environment);

        shell_exec(sprintf(
            'cp -f %s %s',
            $this->bootstrap->getConfigFile('config.php'),
            $this->bootstrap->getSandboxDir() . '/app/etc/config.php'
        ));

        $commandTester = new CommandTester(
            $application->get(Build::NAME)
        );
        $commandTester->execute([]);

        $this->assertSame(0, $commandTester->getStatusCode());

        $commandTester = new CommandTester(
            $application->get(Deploy::NAME)
        );
        $commandTester->execute([]);

        $this->assertSame(0, $commandTester->getStatusCode());
        $this->assertContentPresence($environment);
    }

    /**
     * @return array
     */
    public function defaultDataProvider(): array
    {
        return [
            'default configuration' => [
                'environment' => [],
            ],
            'disabled static content symlinks 3 jobs' => [
                'environment' => [
                    'variables' => [
                        'STATIC_CONTENT_SYMLINK' => Environment::VAL_DISABLED,
                        'STATIC_CONTENT_THREADS' => 3,
                    ],
                ],
            ],
        ];
    }

    /**
     * @param array $environment
     * @dataProvider deployInBuildDataProvider
     */
    public function testDeployInBuild(array $environment)
    {
        $application = $this->bootstrap->createApplication($environment);

        shell_exec(sprintf(
            'cp -f %s %s',
            __DIR__ . '/_files/config_scd_in_build.php',
            $this->bootstrap->getSandboxDir() . '/app/etc/config.php'
        ));

        $commandTester = new CommandTester(
            $application->get(Build::NAME)
        );
        $commandTester->execute([]);

        $this->assertSame(0, $commandTester->getStatusCode());

        $commandTester = new CommandTester(
            $application->get(Deploy::NAME)
        );
        $commandTester->execute([]);

        $this->assertSame(0, $commandTester->getStatusCode());
        $this->assertContentPresence($environment);
    }

    /**
     * @return array
     */
    public function deployInBuildDataProvider(): array
    {
        return [
            'default configuration' => [
                'environment' => [],
            ],
        ];
    }

    /**
     * @param array $environment
     */
    private function assertContentPresence(array $environment)
    {
        $config = $this->bootstrap->mergeConfig($environment);
        $routes = $config->get('routes');

        if ($config->get('skip_front_check') === true || !$routes) {
            return;
        }

        $routes = array_keys($routes);
        $defaultRoute = reset($routes);
        $pageContent = file_get_contents($defaultRoute);

        $this->assertContains(
            'Home Page',
            $pageContent,
            'Check "Home Page" phrase presence'
        );
        $this->assertContains(
            'CMS homepage content goes here.',
            $pageContent,
            'Check "CMS homepage content goes here." phrase presence'
        );
    }

    /**
     * @return array
     */
    public function upgradeDataProvider(): array
    {
        return [
            'default configuration' => [
                'environment' => [],
            ],
        ];
    }
}
