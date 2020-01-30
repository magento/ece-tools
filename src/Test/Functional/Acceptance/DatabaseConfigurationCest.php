<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Test\Functional\Acceptance;

/**
 * This test runs on the latest version of PHP
 */
class DatabaseConfigurationCest extends AbstractCest
{
    /**
     * @param \CliTester $I
     * @param \Codeception\Example $data
     * @throws \Robo\Exception\TaskException
     * @dataProvider databaseConfigurationDataProvider
     */
    public function databaseConfiguration(\CliTester $I, \Codeception\Example $data)
    {
        $I->runEceDockerCommand(
            sprintf(
                'build:compose --mode=production --no-cron --env-cloud-vars="%s"',
                $this->convertEnvFromArrayToJson($data['cloudVariables'])
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
                'cloudVariables' => [
                    'MAGENTO_CLOUD_VARIABLES' => [
                        'DATABASE_CONFIGURATION'=>['some_config' => 'value', '_merge' => true],
                    ],
                ],
                'mergedConfig' => 'some_config',
                'defaultConfig' => 'db',
            ],
            'multiConfig' => [
                'cloudVariables' => [
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
}
