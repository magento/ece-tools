<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Integration;

use Magento\MagentoCloud\Command\Build;
use Magento\MagentoCloud\Command\Deploy;
use Magento\MagentoCloud\Command\PostDeploy;
use Magento\MagentoCloud\Config\Environment;
use Magento\MagentoCloud\Application;
use Magento\MagentoCloud\Config\Deploy\Reader as ConfigReader;
use Magento\MagentoCloud\Filesystem\FileList;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * @inheritdoc
 */
class AcceptanceTest extends AbstractTest
{
    /**
     * @param string $commandName
     * @param Application $application
     * @return void
     */
    private function executeAndAssert($commandName, $application)
    {
        $application->getContainer()->set(
            \Psr\Log\LoggerInterface::class,
            \Magento\MagentoCloud\App\Logger::class
        );
        $commandTester = new CommandTester($application->get($commandName));
        $commandTester->execute([]);
        $this->assertSame(0, $commandTester->getStatusCode());
    }

    /**
     * @param array $environment
     * @param array $expectedConsumersRunnerConfig
     * @dataProvider defaultDataProvider
     */
    public function testDefault(array $environment, array $expectedConsumersRunnerConfig)
    {
        $application = $this->bootstrap->createApplication($environment);

        $this->executeAndAssert(Build::NAME, $application);
        $this->executeAndAssert(Deploy::NAME, $application);
        $this->executeAndAssert(PostDeploy::NAME, $application);

        $this->assertContentPresence($environment);
        $this->assertLogIsSanitized($application);

        /** @var ConfigReader $configReader */
        $configReader = $application->getContainer()->get(ConfigReader::class);
        $config = $configReader->read();

        $this->assertArraySubset($expectedConsumersRunnerConfig, $config);
    }

    /**
     * @return array
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
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
                    ],
                    'directories' => [
                        'document_root_is_pub' => true,
                    ],
                ],
            ],
            'test cron_consumers_runner with array' => [
                'environment' => [
                    'variables' => [
                        'ADMIN_EMAIL' => 'admin@example.com',
                        'CRON_CONSUMERS_RUNNER' => [
                            'cron_run' => true,
                            'max_messages' => 5000,
                            'consumers' => ['test'],
                        ],
                    ],
                ],
                'expectedConsumersRunnerConfig' => [
                    'cron_consumers_runner' => [
                        'cron_run' => true,
                        'max_messages' => 5000,
                        'consumers' => ['test'],
                    ],
                    'directories' => [
                        'document_root_is_pub' => true,
                    ],
                ],
            ],
            'test cron_consumers_runner with wrong array' => [
                'environment' => [
                    'variables' => [
                        'ADMIN_EMAIL' => 'admin@example.com',
                        'CRON_CONSUMERS_RUNNER' => [
                            'cron_run' => 'true',
                            'max_messages' => 5000,
                            'consumers' => ['test'],
                        ],
                    ],
                ],
                'expectedConsumersRunnerConfig' => [
                    'cron_consumers_runner' => [
                        'cron_run' => false,
                        'max_messages' => 5000,
                        'consumers' => ['test'],
                    ],
                    'directories' => [
                        'document_root_is_pub' => true,
                    ],
                ],
            ],
            'test cron_consumers_runner with string' => [
                'environment' => [
                    'variables' => [
                        'ADMIN_EMAIL' => 'admin@example.com',
                        'CRON_CONSUMERS_RUNNER' => '{"cron_run":true, "max_messages":100, "consumers":["test2"]}',
                    ],
                ],
                'expectedConsumersRunnerConfig' => [
                    'cron_consumers_runner' => [
                        'cron_run' => true,
                        'max_messages' => 100,
                        'consumers' => ['test2'],
                    ],
                    'directories' => [
                        'document_root_is_pub' => true,
                    ],
                ],
            ],
            'test cron_consumers_runner with wrong string' => [
                'environment' => [
                    'variables' => [
                        'ADMIN_EMAIL' => 'admin@example.com',
                        'CRON_CONSUMERS_RUNNER' => '{"cron_run":"true", "max_messages":100, "consumers":["test2"]}',
                    ],
                ],
                'expectedConsumersRunnerConfig' => [
                    'cron_consumers_runner' => [
                        'cron_run' => false,
                        'max_messages' => 100,
                        'consumers' => ['test2'],
                    ],
                    'directories' => [
                        'document_root_is_pub' => true,
                    ],
                ],
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
                    ],
                    'directories' => [
                        'document_root_is_pub' => true,
                    ],
                ],
            ],
        ];
    }

    /**
     * This test checks if deployment runs successfully with split build command.
     */
    public function testWithSplitBuildCommand()
    {
        $environment = [
            'environment' => [
                'variables' => [
                    'ADMIN_EMAIL' => 'admin@example.com',
                ],
            ],
        ];
        $application = $this->bootstrap->createApplication($environment);

        $this->executeAndAssert(Build\Generate::NAME, $application);
        $this->executeAndAssert(Build\Transfer::NAME, $application);
        $this->executeAndAssert(Deploy::NAME, $application);
        $this->executeAndAssert(PostDeploy::NAME, $application);

        $this->assertContentPresence($environment);
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

        $this->executeAndAssert(Build::NAME, $application);
        $this->executeAndAssert(Deploy::NAME, $application);
        $this->executeAndAssert(PostDeploy::NAME, $application);

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
        $routes = $this->bootstrap->getEnv('routes', $environment);
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
     * Checks that sensitive data are sanitizing in cloud.log file.
     *
     * @param Application $application
     */
    private function assertLogIsSanitized(Application $application)
    {
        /** @var FileList $fileList */
        $fileList = $application->getContainer()->get(FileList::class);
        $logContent = file_get_contents($fileList->getCloudLog());

        $this->assertContains('--admin-password=\'******\'', $logContent);
        if (strpos($logContent, '--db-password') !== false) {
            $this->assertContains('--db-password=\'******\'', $logContent);
        }
    }
}
