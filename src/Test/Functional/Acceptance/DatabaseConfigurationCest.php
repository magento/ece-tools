<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Test\Functional\Acceptance;

use Magento\MagentoCloud\Test\Functional\Codeception\Docker;

/**
 * This test runs on the latest version of PHP
 */
class DatabaseConfigurationCest extends AbstractCest
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
    }

    /**
     * @param \CliTester $I
     * @param \Codeception\Example $data
     * @throws \Robo\Exception\TaskException
     * @dataProvider databaseConfigurationDataProvider
     */
    public function databaseConfiguration(\CliTester $I, \Codeception\Example $data)
    {
        $I->assertTrue($I->runEceToolsCommand('build', Docker::BUILD_CONTAINER));
        $I->startEnvironment();
        $I->assertTrue($I->runEceToolsCommand(
            'deploy',
            Docker::DEPLOY_CONTAINER,
            $data['cloudVariables']
        ));
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
                'defaultConfig' => 'db.magento2.docker',
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
                'defaultConfig' => 'db.magento2.docker',
            ],
        ];
    }
}
