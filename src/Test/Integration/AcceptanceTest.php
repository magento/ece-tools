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
            "cd %s && php bin/magento setup:uninstall -n",
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
            "cp -f %s %s",
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

        if (getenv('MAGENTO_HOST_NAME')) {
            $pageContent = file_get_contents('http://' . getenv('MAGENTO_HOST_NAME'));

            $this->assertContains('Home', $pageContent);
        }
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
            "cp -f %s %s",
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
}
