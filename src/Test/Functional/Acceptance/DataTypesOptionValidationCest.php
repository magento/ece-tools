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
 * @group php83
 */
class DataTypesOptionValidationCest extends AbstractCest
{
    /**
     * @var string
     */
    protected $magentoCloudTemplate = '2.4.7-beta-test';

    /**
     * @param \CliTester $I
     * @param \Codeception\Example $data
     * @throws \Robo\Exception\TaskException
     * @dataProvider dataTypesDataProvider
     */
    public function dataTypesValidationOnDeploy(\CliTester $I, \Codeception\Example $data): void
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

        $log = $I->grabFileContent('/var/log/cloud.log');
        $I->assertStringContainsString($data['expectedError'], $log);
    }

    /**
     * @return array
     */
    protected function dataTypesDataProvider(): array
    {
        return [
            'string_instead_integer' => [
                'variables' => [
                    'MAGENTO_CLOUD_VARIABLES' => [
                        'SCD_THREADS' => 'one',
                    ],
                ],
                'expectedError' => 'SCD_THREADS has wrong value',
            ],
            'integer_instead_boolean' => [
                'variables' => [
                    'MAGENTO_CLOUD_VARIABLES' => [
                        'CLEAN_STATIC_FILES' => 1,
                    ],
                ],
                'expectedError' => 'CLEAN_STATIC_FILES has wrong value',
            ],
        ];
    }
}
