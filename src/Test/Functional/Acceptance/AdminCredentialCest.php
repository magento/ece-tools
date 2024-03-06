<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Test\Functional\Acceptance;

/**
 * This test runs on the latest version of PHP
 * @group php83
 */
class AdminCredentialCest extends AbstractCest
{
    /**
     * @var string
     */
    protected $magentoCloudTemplate = '2.4.7-beta-test';

    /**
     * @param \CliTester $I
     */
    public function _before(\CliTester $I): void
    {
        parent::_before($I);

        $I->copyFileToWorkDir('files/debug_logging/.magento.env.yaml', '.magento.env.yaml');
    }

    /**
     * @param \CliTester $I
     * @param \Codeception\Example $data
     * @throws \Robo\Exception\TaskException
     * @dataProvider installWithoutAdminEmailDataProvider
     */
    public function testInstallWithoutAdminEmail(\CliTester $I, \Codeception\Example $data): void
    {
        $I->generateDockerCompose(
            sprintf(
                '--mode=production --env-vars="%s"',
                $this->convertEnvFromArrayToJson($data['variables'])
            )
        );
        $I->runDockerComposeCommand('run build cloud-build');
        $I->startEnvironment();
        $I->runDockerComposeCommand('run build cloud-build');
        $I->runDockerComposeCommand('run deploy cloud-deploy');
        $I->amOnPage('/');
        $I->see('Home page');
        $I->see('CMS homepage content goes here.');

        $log = $I->grabFileContent('/var/log/cloud.log');
        $I->assertStringContainsString($data['installMessage'], $log);
        $I->assertStringNotContainsString('--admin-user', $log);
        $I->assertStringNotContainsString('--admin-firstname', $log);
        $I->assertStringNotContainsString('--admin-lastname', $log);
        $I->assertStringNotContainsString('--admin-email', $log);
        $I->assertStringNotContainsString('--admin-password', $log);

        // Upgrade
        $I->runDockerComposeCommand('run deploy cloud-deploy');

        $I->assertStringContainsString($data['upgradeMessage'], $I->grabFileContent('/var/log/cloud.log'));
    }

    /**
     * @return array
     */
    protected function installWithoutAdminEmailDataProvider(): array
    {
        return [
            [
                'variables' => ['MAGENTO_CLOUD_VARIABLES' => []],
                'installMessage' => '',
                'upgradeMessage' => '',
            ],
            [
                'variables' => ['MAGENTO_CLOUD_VARIABLES' => ['ADMIN_USERNAME' => 'MyLogin']],
                'installMessage' => 'The following admin data was ignored and an admin was not created because'
                    . ' admin email is not set: admin login',
                'upgradeMessage' => 'The following admin data is required to create an admin user during initial'
                    . ' installation only and is ignored during upgrade process: admin login',
            ],
        ];
    }

    /**
     * @param \CliTester $I
     * @param \Codeception\Example $data
     * @throws \Robo\Exception\TaskException
     * @dataProvider installWithDifferentVariablesDataProvider
     */
    public function testInstallWithDifferentVariables(\CliTester $I, \Codeception\Example $data)
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
        $I->amOnPage('/');
        $I->see('Home page');
        $I->see('CMS homepage content goes here.');

        $credentialsEmail = $I->grabFileContent('/var/credentials_email.txt');
        $I->assertStringContainsString($data['expectedAdminEmail'], $credentialsEmail);
        $I->assertStringContainsString($data['expectedAdminUsername'], $credentialsEmail);
        $I->assertStringContainsString($data['expectedAdminUrl'], $credentialsEmail);

        $log = $I->grabFileContent('/var/log/cloud.log');
        $I->assertStringContainsString('--admin-user', $log);
        $I->assertStringContainsString('--admin-firstname', $log);
        $I->assertStringContainsString('--admin-lastname', $log);
        $I->assertStringContainsString('--admin-email', $log);
        $I->assertStringContainsString('--admin-password', $log);
        $I->assertStringNotContainsString(
            'The following admin data was ignored and an admin was not created because admin email is not set',
            $log
        );

        // Upgrade
        $I->runDockerComposeCommand('run deploy cloud-deploy');

        $I->assertStringNotContainsString(
            'The following admin data is required to create an admin user during initial installation only'
            . ' and is ignored during upgrade process: admin login',
            $I->grabFileContent('/var/log/cloud.log')
        );
    }

    /**
     * @return array
     */
    protected function installWithDifferentVariablesDataProvider()
    {
        return [
            [
                'variables' => [
                    'MAGENTO_CLOUD_VARIABLES' => ['ADMIN_EMAIL' => 'admin@example.com'],
                ],
                'expectedAdminEmail' => 'admin@example.com',
                'expectedAdminUsername' => 'admin',
                'expectedAdminUrl' => 'admin',
            ],
            [
                'variables' => [
                    'MAGENTO_CLOUD_VARIABLES' => [
                        'ADMIN_EMAIL' => 'admin2@example.com',
                        'ADMIN_URL' => 'root',
                        'ADMIN_USERNAME' => 'myusername',
                    ],
                ],
                'expectedAdminEmail' => 'admin2@example.com',
                'expectedAdminUsername' => 'root',
                'expectedAdminUrl' => 'myusername',
            ]
        ];
    }
}
