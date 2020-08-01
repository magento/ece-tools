<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Test\Functional\Acceptance;

/**
 * This test runs on the latest version of PHP
 *
 * @group php74
 */
class DatabaseConfigurationCest extends AbstractCest
{
    /**
     * @param \CliTester $I
     * @param \Codeception\Example $data
     * @throws \Robo\Exception\TaskException
     * @dataProvider databaseConfigurationDataProvider
     */
    public function databaseConfiguration(\CliTester $I, \Codeception\Example $data): void
    {
        $I->runEceDockerCommand(
            sprintf(
                'build:compose --mode=production --env-vars="%s"',
                $this->convertEnvFromArrayToJson($data['variables'])
            )
        );
        $I->runDockerComposeCommand('run build cloud-build');
        $I->startEnvironment();
        $I->runDockerComposeCommand('run deploy cloud-deploy');

        $file = $I->grabFileContent('/app/etc/env.php');
        $I->assertContains($data['mergedConfig'], $file);
        $I->assertContains($data['defaultConfig'], $file);
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
            $I->assertContains("'table_prefix' => 'ece_'", $file, 'Wrong table prefix in app/etc/env.php');
            $I->amOnPage('/');
            $I->see('Home page');
            $I->see('CMS homepage content goes here.');
        };

        $I->runEceDockerCommand(
            sprintf(
                'build:compose --mode=production --env-vars="%s"',
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
}
