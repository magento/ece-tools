<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Test\Functional\Acceptance;

use Magento\CloudDocker\Test\Functional\Codeception\Docker;

/**
 * This test runs on the latest version of PHP
 *
 * @group php83
 */
class DatabaseConfigurationCest extends AbstractCest
{
    /**
     * @var string
     */
    protected $magentoCloudTemplate = '2.4.7-beta-test';

    /**
     * @param \CliTester $I
     * @param \Codeception\Example $data
     * @throws \Robo\Exception\TaskException
     * @dataProvider databaseConfigurationDataProvider
     */
    public function databaseConfiguration(\CliTester $I, \Codeception\Example $data): void
    {
        $I->generateDockerCompose(
            sprintf(
                '--mode=production --env-vars="%s"',
                $this->convertEnvFromArrayToJson($data['variables'])
            )
        );
        $I->runDockerComposeCommand('run build cloud-build');
        $I->startEnvironment();
        $I->runDockerComposeCommand('run deploy cloud-deploy');

        $file = $I->grabFileContent('/app/etc/env.php');
        $I->assertStringContainsString($data['mergedConfig'], $file);
        $I->assertStringContainsString($data['defaultConfig'], $file);
    }

    /**
     * @return array
     */
    protected function databaseConfigurationDataProvider(): array
    {
        return [
            'singleConfig' => [
                'variables' => [
                    'MAGENTO_CLOUD_VARIABLES' => [
                        'DATABASE_CONFIGURATION'=>['some_config' => 'value', '_merge' => true],
                    ],
                ],
                'mergedConfig' => 'some_config',
                'defaultConfig' => 'db',
            ],
            'multiConfig' => [
                'variables' => [
                    'MAGENTO_CLOUD_VARIABLES' => [
                        'DATABASE_CONFIGURATION'=>[
                            'connection' => [
                                'default' => [
                                    'engine' => 'innodb',
                                    'initStatements' => 'SET NAMES utf8;',
                                    'active' => '1',
                                    'driver_options' => [
                                        '1001' => '1',
                                    ],
                                ],
                                'indexer' => [
                                    'driver_options' => [
                                        '1001' => '1',
                                    ],
                                ],
                            ],
                            '_merge' => true,
                        ],
                    ],
                ],
                'mergedConfig' => '1001',
                'defaultConfig' => 'db',
            ],
        ];
    }

    /**
     * Check that magento can be installed and updated with configured table prefixes
     *
     * @param \CliTester $I
     * @throws \Robo\Exception\TaskException
     */
    public function installAndRedeployWithTablePrefix(\CliTester $I)
    {
        $checkResult = function (\CliTester $I) {
            $file = $I->grabFileContent('/app/etc/env.php');
            $I->assertStringContainsString("'table_prefix' => 'ece_'", $file, 'Wrong table prefix in app/etc/env.php');
            $I->amOnPage('/');
            $I->see('Home page');
            $I->see('CMS homepage content goes here.');
        };

        $I->generateDockerCompose(
            sprintf(
                '--mode=production --env-vars="%s"',
                $this->convertEnvFromArrayToJson([
                    'MAGENTO_CLOUD_VARIABLES' => [
                        'DATABASE_CONFIGURATION'=>[
                            'table_prefix' => 'ece_',
                            '_merge' => true,
                        ],
                    ],
                ])
            )
        );

        $I->assertTrue($I->runDockerComposeCommand('run build cloud-build'));
        $I->assertTrue($I->startEnvironment());
        $I->assertTrue(
            $I->runDockerComposeCommand('run deploy cloud-deploy'),
            'Installation with table prefixes failed'
        );
        $I->runDockerComposeCommand('run deploy cloud-post-deploy');
        $checkResult($I);

        $I->assertTrue($I->runDockerComposeCommand('run deploy cloud-deploy'), 'Re-deployment failed');
        $I->runDockerComposeCommand('run deploy cloud-post-deploy');
        $checkResult($I);
    }

    /**
     * Tests scenario when additional custom db configuration added into
     * DATABASE_CONFIGURATION option in .magento.env.yaml
     */
    public function customDataBaseConfigurationMagentoEnvYaml(\CliTester $I)
    {
        $I->copyFileToWorkDir('files/custom_db_configuration/.magento.env.yaml', '.magento.env.yaml');
        $I->generateDockerCompose('--mode=production');
        $I->assertTrue($I->runDockerComposeCommand('run build cloud-build'));
        $I->assertTrue($I->startEnvironment());
        $I->assertTrue(
            $I->runDockerComposeCommand('run deploy cloud-deploy'),
            'Installation with additional custom connection failed'
        );
        $I->runDockerComposeCommand('run deploy cloud-post-deploy');

        $config = $this->getConfig($I);
        $I->assertArrayHasKey('custom', $config['db']['connection']);
        $I->assertArrayHasKey('custom', $config['db']['slave_connection']);
        $I->assertArrayHasKey('custom', $config['resource']);
    }

    /**
     * @param \CliTester $I
     * @return array
     */
    private function getConfig(\CliTester $I): array
    {
        $destination = sys_get_temp_dir() . '/app/etc/env.php';
        $I->assertTrue($I->downloadFromContainer('/app/etc/env.php', $destination, Docker::DEPLOY_CONTAINER));
        return require $destination;
    }
}
