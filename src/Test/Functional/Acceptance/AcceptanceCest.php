<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Test\Functional\Acceptance;

use Magento\MagentoCloud\Test\Functional\Codeception\Docker;
use Magento\MagentoCloud\Util\ArrayManager;

/**
 * This test runs on the latest version of PHP
 *
 * 1. Test successful deploy
 * 2. Test content presence
 * 3. Test config dump
 * 4. Test content presence
 */
class AcceptanceCest extends AbstractCest
{
    /**
     * @param \CliTester $I
     * @throws \Robo\Exception\TaskException
     */
    public function _before(\CliTester $I)
    {
        parent::_before($I);
        $I->cloneTemplate();
        $I->addEceComposerRepo();
        $I->uploadToContainer('files/debug_logging/.magento.env.yaml', '/.magento.env.yaml', Docker::BUILD_CONTAINER);
    }

    /**
     * @param \CliTester $I
     * @param \Codeception\Example $data
     * @throws \Robo\Exception\TaskException
     * @dataProvider defaultDataProvider
     */
    public function testDefault(\CliTester $I, \Codeception\Example $data)
    {
        $I->assertTrue($I->runEceToolsCommand('build', Docker::BUILD_CONTAINER));
        $I->startEnvironment();
        $I->assertTrue($I->runEceToolsCommand(
            'deploy',
            Docker::DEPLOY_CONTAINER,
            $data['cloudVariables'],
            $data['rawVariables']
        ));
        $I->assertTrue($I->runEceToolsCommand('post-deploy', Docker::DEPLOY_CONTAINER));

        $I->amOnPage('/');
        $I->see('Home page');

        $destination = sys_get_temp_dir() . '/app/etc/env.php';
        $I->assertTrue($I->downloadFromContainer('/app/etc/env.php', $destination, Docker::DEPLOY_CONTAINER));
        $config = require $destination;
        $I->assertArraySubset($data['expectedConfig'], $config);

        $I->assertTrue($I->runEceToolsCommand('config:dump', Docker::DEPLOY_CONTAINER));
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

        $log = $I->grabFileContent('/var/log/cloud.log');
        $I->assertContains('--admin-password=\'******\'', $log);
        if (strpos($log, '--db-password') !== false) {
            $I->assertContains('--db-password=\'******\'', $log);
        }
    }

    /**
     * @return array
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
     * @param \CliTester $I
     * @throws \Robo\Exception\TaskException
     */
    public function testWithSplitBuildCommand(\CliTester $I)
    {
        $I->assertTrue($I->runEceToolsCommand('build:generate', Docker::BUILD_CONTAINER));
        $I->assertTrue($I->runEceToolsCommand('build:transfer', Docker::BUILD_CONTAINER));
        $I->startEnvironment();
        $I->assertTrue($I->runEceToolsCommand('deploy', Docker::DEPLOY_CONTAINER));
        $I->assertTrue($I->runEceToolsCommand('post-deploy', Docker::DEPLOY_CONTAINER));
        $I->amOnPage('/');
        $I->see('Home page');
        $I->see('CMS homepage content goes here.');
    }

    /**
     * @param \CliTester $I
     * @throws \Robo\Exception\TaskException
     */
    public function testDeployInBuild(\CliTester $I)
    {
        $tmpConfig = sys_get_temp_dir() . '/app/etc/config.php';
        $I->startEnvironment();
        $I->assertTrue($I->runEceToolsCommand('build', Docker::BUILD_CONTAINER));
        $I->assertTrue($I->runEceToolsCommand('deploy', Docker::DEPLOY_CONTAINER));
        $I->assertTrue($I->runEceToolsCommand('post-deploy', Docker::DEPLOY_CONTAINER));
        $I->assertTrue($I->runEceToolsCommand('config:dump', Docker::DEPLOY_CONTAINER));
        $I->assertNotContains(
            'Static content deployment was performed during the build phase or disabled. '
                . 'Skipping deploy phase static content compression.',
            $I->grabFileContent('/var/log/cloud.log')
        );
        $I->amOnPage('/');
        $I->see('Home page');
        $I->see('CMS homepage content goes here.');
        $I->assertTrue($I->downloadFromContainer('/app/etc/config.php', $tmpConfig, Docker::DEPLOY_CONTAINER));

        $I->assertTrue($I->cleanUpEnvironment());

        $I->assertTrue($I->cloneTemplate());
        $I->assertTrue($I->addEceComposerRepo());
        $I->assertTrue($I->uploadToContainer($tmpConfig, '/app/etc/config.php', Docker::BUILD_CONTAINER));
        $I->assertTrue($I->runEceToolsCommand('build', Docker::BUILD_CONTAINER));
        $I->assertTrue($I->runEceToolsCommand('deploy', Docker::DEPLOY_CONTAINER));
        $I->assertTrue($I->runEceToolsCommand('post-deploy', Docker::DEPLOY_CONTAINER));
        $I->startEnvironment();
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
