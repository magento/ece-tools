<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Integration;

use Magento\MagentoCloud\Command\Build;
use Magento\MagentoCloud\Command\Deploy;
use Magento\MagentoCloud\Command\Prestart;
use Magento\MagentoCloud\Config\Environment;
use Magento\MagentoCloud\Config\Deploy\Reader as ConfigReader;
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

        $this->bootstrap->execute(sprintf(
            'cd %s && php bin/magento module:enable --all',
            $this->bootstrap->getSandboxDir()
        ));
    }

    /**
     * @inheritdoc
     */
    protected function tearDown()
    {
        $this->bootstrap->execute(sprintf(
            'cd %s && php bin/magento setup:uninstall -n',
            $this->bootstrap->getSandboxDir()
        ));
    }

    /**
     * @param array $environment
     * @param array $expectedConsumersRunnerConfig
     * @dataProvider defaultDataProvider
     */
    public function testDefault(array $environment, array $expectedConsumersRunnerConfig)
    {
        $application = $this->bootstrap->createApplication($environment);

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

        $commandTester = new CommandTester(
            $application->get(Prestart::NAME)
        );
        $commandTester->execute([]);

        $this->assertSame(0, $commandTester->getStatusCode());

        $this->assertContentPresence($environment);

        /** @var ConfigReader $configReader */
        $configReader = $application->getContainer()->get(ConfigReader::class);
        $config = $configReader->read();

        $this->assertArraySubset($expectedConsumersRunnerConfig, $config);
    }

    /**
     * @return array
     */
    public function defaultDataProvider(): array
    {
        return [
            'default configuration' => [
                'environment' => [
                    'variables' => [
                        'ADMIN_EMAIL' => 'admin@example.com',
                    ],
                ],
                'expectedConsumersRunnerConfig' => [
                    'cron_consumers_runner' => [
                        'cron_run' => false,
                        'max_messages' => 10000,
                        'consumers' => [],
                    ]
                ]
            ],
            'test cron_consumers_runner with array' => [
                'environment' => [
                    'variables' => [
                        'ADMIN_EMAIL' => 'admin@example.com',
                        'CRON_CONSUMERS_RUNNER' => [
                            'cron_run' => "true",
                            'max_messages' => 5000,
                            'consumers' => ['test'],
                        ]
                    ],
                ],
                'expectedConsumersRunnerConfig' => [
                    'cron_consumers_runner' => [
                        'cron_run' => true,
                        'max_messages' => 5000,
                        'consumers' => ['test'],
                    ]
                ]
            ],
            'test cron_consumers_runner with string' => [
                'environment' => [
                    'variables' => [
                        'ADMIN_EMAIL' => 'admin@example.com',
                        'CRON_CONSUMERS_RUNNER' => '{"cron_run":"true", "max_messages":100, "consumers":["test2"]}',
                    ],
                ],
                'expectedConsumersRunnerConfig' => [
                    'cron_consumers_runner' => [
                        'cron_run' => true,
                        'max_messages' => 100,
                        'consumers' => ['test2'],
                    ]
                ]
            ],
            'disabled static content symlinks 3 jobs' => [
                'environment' => [
                    'variables' => [
                        'ADMIN_EMAIL' => 'admin@example.com',
                        'STATIC_CONTENT_SYMLINK' => Environment::VAL_DISABLED,
                        'STATIC_CONTENT_THREADS' => 3,
                    ],
                ],
                'expectedConsumersRunnerConfig' => [
                    'cron_consumers_runner' => [
                        'cron_run' => false,
                        'max_messages' => 10000,
                        'consumers' => [],
                    ]
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

        $this->bootstrap->execute(sprintf(
            'cp -f %s %s',
            __DIR__ . '/_files/config_scd_in_build.php',
            $this->bootstrap->getSandboxDir() . '/app/etc/config.php'
        ));
        $this->bootstrap->execute(sprintf(
            'cd %s && php bin/magento module:enable --all',
            $this->bootstrap->getSandboxDir()
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

        $commandTester = new CommandTester(
            $application->get(Prestart::NAME)
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
                'environment' => [
                    'variables' => [
                        'ADMIN_EMAIL' => 'admin@example.com',
                    ],
                ],
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
}
