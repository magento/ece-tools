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
class DataTypesOptionValidationCest extends AbstractCest
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
     * @dataProvider dataTypesDataProvider
     */
    public function dataTypesValidationOnDeploy(\CliTester $I, \Codeception\Example $data)
    {
        $I->assertTrue($I->runEceToolsCommand('build', Docker::BUILD_CONTAINER));
        $I->startEnvironment();
        $I->assertTrue($I->runEceToolsCommand(
            'deploy',
            Docker::DEPLOY_CONTAINER,
            $data['cloudVariables']
        ));
        $log = $I->grabFileContent('/var/log/cloud.log');
        $I->assertContains($data['expectedError'], $log);
    }

    /**
     * @return array
     */
    protected function dataTypesDataProvider(): array
    {
        return [
            'string_instead_integer' => [
                'cloudVariables' => [
                    'MAGENTO_CLOUD_VARIABLES' => [
                        'SCD_THREADS' => 'one',
                    ],
                ],
                'expectedError' => 'SCD_THREADS has wrong value',
            ],
            'integer_instead_boolean' => [
                'cloudVariables' => [
                    'MAGENTO_CLOUD_VARIABLES' => [
                        'CLEAN_STATIC_FILES' => 1,
                    ],
                ],
                'expectedError' => 'CLEAN_STATIC_FILES has wrong value',
            ],
        ];
    }
}
