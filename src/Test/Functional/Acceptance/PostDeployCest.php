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
class PostDeployCest extends AbstractCest
{
    /**
     * @var string
     */
    protected $magentoCloudTemplate = '2.4.7-beta-test';

    /**
     * @param \CliTester $I
     * @param \Codeception\Example $data
     * @throws \Robo\Exception\TaskException
     * @dataProvider postDeployDataProvider
     */
    public function testPostDeploy(\CliTester $I, \Codeception\Example $data): void
    {
        $I->generateDockerCompose(
            sprintf(
                '--mode=production --env-vars="%s"',
                $this->convertEnvFromArrayToJson($data['variables'])
            )
        );

        $I->copyFileToWorkDir('files/scdondemand/.magento.env.yaml', '.magento.env.yaml');

        $I->runDockerComposeCommand('run build cloud-build');
        $I->startEnvironment();
        $I->runDockerComposeCommand('run deploy cloud-deploy');
        $I->runDockerComposeCommand('run deploy cloud-post-deploy');

        $log = $I->grabFileContent('/var/log/cloud.log');
        $I->assertStringContainsString('INFO: Starting scenario(s): scenario/post-deploy.xml', $log);
        $I->assertStringContainsString('DEBUG: Running step: is-deploy-failed', $log);
        $I->assertStringContainsString('DEBUG: Running step: validate-config', $log);
        $I->assertStringContainsString('DEBUG: Running step: enable-cron', $log);
        $I->assertStringContainsString('DEBUG: Running step: clean-cache', $log);
        $I->assertStringContainsString('DEBUG: Running step: warm-up', $log);
        $I->assertStringContainsString('DEBUG: Running step: time-to-first-byte', $log);
    }

    /**
     * @return array
     */
    protected function postDeployDataProvider(): array
    {
        return [
            [
                'variables' => [
                    'MAGENTO_CLOUD_VARIABLES' => ['ADMIN_EMAIL' => 'admin@example.com']
                ],
            ],
            [
                'variables' => ['MAGENTO_CLOUD_VARIABLES' => []]
            ],
        ];
    }

    /**
     * @param \CliTester $I
     * @throws \Robo\Exception\TaskException
     */
    public function testPostDeployIsNotRun(\CliTester $I): void
    {
        $I->generateDockerCompose('--mode=production');
        $I->copyFileToWorkDir('files/wrong_db_configuration/.magento.env.yaml', '.magento.env.yaml');

        $I->runDockerComposeCommand('run build cloud-build');
        $I->startEnvironment();
        $I->assertFalse($I->runDockerComposeCommand('run deploy cloud-deploy'));
        $I->seeInOutput('Variable DATABASE_CONFIGURATION is not configured properly');
        $I->runDockerComposeCommand('run deploy cloud-post-deploy');

        $log = $I->grabFileContent('/var/log/cloud.log');
        $I->assertStringContainsString('Fix configuration with given suggestions', $log);
        $I->assertStringContainsString('Post-deploy is skipped because deploy was failed.', $log);
        $I->assertStringNotContainsString('NOTICE: Starting post-deploy.', $log);
        $I->assertStringNotContainsString('INFO: Warmed up page:', $log);
        $I->assertStringNotContainsString('NOTICE: Post-deploy is complete.', $log);
    }
}
