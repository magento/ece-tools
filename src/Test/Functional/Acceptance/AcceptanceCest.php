<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Test\Functional\Acceptance;

use CliTester;
use Robo\Exception\TaskException;
use Codeception\Example;
use Magento\CloudDocker\Test\Functional\Codeception\Docker;
use Magento\MagentoCloud\Util\ArrayManager;

/**
 * This test runs on the latest version of PHP
 *
 * 1. Test successful deploy
 * 2. Test content presence
 * 3. Test config dump
 * 4. Test content presence
 */
class AcceptanceCest extends AbstractInstallCest
{
    /**
     * @param CliTester $I
     *
     * @throws TaskException
     */
    public function _before(CliTester $I): void
    {
        parent::_before($I);

        $I->copyToWorkDir('files/debug_logging/.magento.env.yaml', '/.magento.env.yaml');
    }

    /**
     * @param CliTester $I
     * @param Example $data
     *
     * @throws TaskException
     *
     * @dataProvider defaultDataProvider
     */
    public function testDefault(\CliTester $I, \Codeception\Example $data): void
    {
        $I->runEceDockerCommand(
            sprintf(
                'build:compose --mode=production --no-cron --env-cloud-vars="%s" --env-raw-vars="%s"',
                $this->convertEnvFromArrayToJson($data['cloudVariables']),
                $this->convertEnvFromArrayToJson($data['rawVariables'])
            )
        );
        $I->runDockerComposeCommand('run build cloud-build');
        $I->startEnvironment();
        $I->runDockerComposeCommand('run deploy cloud-deploy');
        $I->amOnPage('/');
        $I->see('Home page');
        $I->see('CMS homepage content goes here.');

        $destination = sys_get_temp_dir() . '/app/etc/env.php';
        $I->assertTrue($I->downloadFromContainer('/app/etc/env.php', $destination, Docker::DEPLOY_CONTAINER));
        $config = require $destination;
        $I->assertArraySubset($data['expectedConfig'], $config);

        $I->assertTrue($I->runDockerComposeCommand('run deploy ece-command config:dump'));
        $destination = sys_get_temp_dir() . '/app/etc/config.php';
        $I->assertTrue($I->downloadFromContainer('/app/etc/config.php', $destination, Docker::DEPLOY_CONTAINER));
        $config = require $destination;

        $arrayManager = new ArrayManager();
        $flattenKeysConfig = implode(array_keys($arrayManager->flatten($config, '#')));

        $I->assertContains('#modules', $flattenKeysConfig);
        $I->assertContains('#scopes', $flattenKeysConfig);
        $I->assertContains('#system/default/general/locale/code', $flattenKeysConfig);
        $I->assertContains('#system/default/dev/static/sign', $flattenKeysConfig);
        $I->assertContains('#system/default/dev/front_end_development_workflow', $flattenKeysConfig);
        $I->assertContains('#system/default/dev/template', $flattenKeysConfig);
        $I->assertContains('#system/default/dev/js', $flattenKeysConfig);
        $I->assertContains('#system/default/dev/css', $flattenKeysConfig);
        $I->assertContains('#system/stores', $flattenKeysConfig);
        $I->assertContains('#system/websites', $flattenKeysConfig);
        $I->assertContains('#admin_user/locale/code', $flattenKeysConfig);

        $I->amOnPage('/');
        $I->see('Home page');
        $I->see('CMS homepage content goes here.');

        $log = $I->grabFileContent('/var/log/cloud.log');
        $I->assertContains('--admin-password=\'******\'', $log);
        if (strpos($log, '--db-password') !== false) {
            $I->assertContains('--db-password=\'******\'', $log);
        }
    }

    /**
     * @return array
     *
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    protected function defaultDataProvider(): array
    {
        return [
            'default configuration' => [
                'cloudVariables' => [
                    'MAGENTO_CLOUD_VARIABLES' => [
                        'ADMIN_EMAIL' => 'admin@example.com',
                    ],
                ],
                'rawVariables' => [],
                'expectedConfig' => [
                    'cron_consumers_runner' => [
                        'cron_run' => false,
                        'max_messages' => 10000,
                        'consumers' => [],
                    ],
                    'directories' => [
                        'document_root_is_pub' => true,
                    ],
                    'lock' => [
                        'provider' => 'db',
                        'config' => [
                            'prefix' => null,
                        ],
                    ],
                ],
            ],
            'test cron_consumers_runner with array and there is MAGENTO_CLOUD_LOCKS_DIR' => [
                'cloudVariables' => [
                    'MAGENTO_CLOUD_VARIABLES' => [
                        'ADMIN_EMAIL' => 'admin@example.com',
                        'CRON_CONSUMERS_RUNNER' => [
                            'cron_run' => true,
                            'max_messages' => 5000,
                            'consumers' => ['test'],
                        ],
                    ],
                ],
                'rawVariables' => [
                    'MAGENTO_CLOUD_LOCKS_DIR' => '/tmp/locks',
                ],
                'expectedConfig' => [
                    'cron_consumers_runner' => [
                        'cron_run' => true,
                        'max_messages' => 5000,
                        'consumers' => ['test'],
                    ],
                    'directories' => [
                        'document_root_is_pub' => true,
                    ],
                    'lock' => [
                        'provider' => 'file',
                        'config' => [
                            'path' => '/tmp/locks',
                        ],
                    ],
                ],
            ],
            'test cron_consumers_runner with wrong array, there is MAGENTO_CLOUD_LOCKS_DIR, LOCK_PROVIDER is db' => [
                'cloudVariables' => [
                    'MAGENTO_CLOUD_VARIABLES' => [
                        'ADMIN_EMAIL' => 'admin@example.com',
                        'LOCK_PROVIDER' => 'db',
                        'CRON_CONSUMERS_RUNNER' => [
                            'cron_run' => 'true',
                            'max_messages' => 5000,
                            'consumers' => ['test'],
                        ],
                    ],
                ],
                'rawVariables' => [
                    'MAGENTO_CLOUD_LOCKS_DIR' => '/tmp/locks',
                ],
                'expectedConfig' => [
                    'cron_consumers_runner' => [
                        'cron_run' => false,
                        'max_messages' => 5000,
                        'consumers' => ['test'],
                    ],
                    'directories' => [
                        'document_root_is_pub' => true,
                    ],
                    'lock' => [
                        'provider' => 'db',
                        'config' => [
                            'prefix' => null,
                        ],
                    ],
                ],
            ],
            'test cron_consumers_runner with string' => [
                'cloudVariables' => [
                    'MAGENTO_CLOUD_VARIABLES' => [
                        'ADMIN_EMAIL' => 'admin@example.com',
                        'CRON_CONSUMERS_RUNNER' => '{"cron_run":true, "max_messages":100, "consumers":["test2"]}',
                    ],
                ],
                'rawVariables' => [],
                'expectedConfig' => [
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
                'cloudVariables' => [
                    'MAGENTO_CLOUD_VARIABLES' => [
                        'ADMIN_EMAIL' => 'admin@example.com',
                        'CRON_CONSUMERS_RUNNER' => '{"cron_run":"true", "max_messages":100, "consumers":["test2"]}',
                    ],
                ],
                'rawVariables' => [],
                'expectedConfig' => [
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
                'cloudVariables' => [
                    'MAGENTO_CLOUD_VARIABLES' => [
                        'ADMIN_EMAIL' => 'admin@example.com',
                        'STATIC_CONTENT_SYMLINK' => 'disabled',
                        'STATIC_CONTENT_THREADS' => 3,
                    ],
                ],
                'rawVariables' => [],
                'expectedConfig' => [
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
     * @param CliTester $I
     * @throws TaskException
     */
    public function testWithOldNonSplitBuildCommand(\CliTester $I): void
    {
        $config = $I->readAppMagentoYaml();
        $config['hooks']['build'] = 'set -e' . PHP_EOL . 'php ./vendor/bin/ece-tools build' . PHP_EOL;
        $I->writeAppMagentoYaml($config);

        $I->runEceDockerCommand('build:compose --mode=production --no-cron');
        $I->runDockerComposeCommand('run build cloud-build');
        $I->startEnvironment();
        $I->runDockerComposeCommand('run deploy cloud-deploy');
        $I->amOnPage('/');
        $I->see('Home page');
        $I->see('CMS homepage content goes here.');
    }

    /**
     * @param CliTester $I
     *
     * @throws TaskException
     */
    public function testDeployInBuild(\CliTester $I): void
    {
        $tmpConfig = sys_get_temp_dir() . '/app/etc/config.php';
        $I->runEceDockerCommand('build:compose --mode=production --no-cron');
        $I->runDockerComposeCommand('run build cloud-build');
        $I->startEnvironment();
        $I->runDockerComposeCommand('run deploy cloud-deploy');
        $I->amOnPage('/');
        $I->see('Home page');
        $I->see('CMS homepage content goes here.');
        $I->runDockerComposeCommand('run deploy ece-command config:dump');
        $I->assertNotContains(
            'Static content deployment was performed during the build phase or disabled. '
            . 'Skipping deploy phase static content compression.',
            $I->grabFileContent('/var/log/cloud.log')
        );
        $I->amOnPage('/');
        $I->see('Home page');
        $I->see('CMS homepage content goes here.');
        $I->assertTrue(
            $I->downloadFromContainer('/app/etc/config.php', $tmpConfig, Docker::DEPLOY_CONTAINER),
            'Cannot download config.php from Docker'
        );

        $I->assertTrue($I->stopEnvironment());
        $I->assertTrue($I->copyToWorkDir($tmpConfig, '/app/etc/config.php'));
        $I->runDockerComposeCommand('run build cloud-build');
        $I->startEnvironment();
        $I->runDockerComposeCommand('run deploy cloud-deploy');
        $I->assertContains(
            'Static content deployment was performed during the build phase or disabled. '
            . 'Skipping deploy phase static content compression.',
            $I->grabFileContent('/var/log/cloud.log')
        );
        $I->amOnPage('/');
        $I->see('Home page');
        $I->see('CMS homepage content goes here.');
    }
}
