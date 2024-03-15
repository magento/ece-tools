<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Test\Functional\Acceptance;

use Magento\MagentoCloud\App\Error;

/**
 * This test cover functionality of state-aware error codes.
 * Checks that failed scenario returns correct error code different to 1 or 255.
 * Checks that var/log/cloud.error.log file was created and contains correct data.
 * Checks that `ece-tools error:show` command returns correct errors info
 *
 * @group php83
 */
class ErrorCodesCest extends AbstractCest
{
    /**
     * @var string
     */
    protected $magentoCloudTemplate = '2.4.7-beta-test';

    /**
     * @param \CliTester $I
     * @throws \Robo\Exception\TaskException
     */
    public function testDeployFailed(\CliTester $I): void
    {
        $I->generateDockerCompose('--mode=production');
        $I->copyFileToWorkDir('files/error_codes/.magento.env.fail.yaml', '.magento.env.yaml');

        $I->runDockerComposeCommand('run build cloud-build');
        $I->startEnvironment();
        $I->assertFalse($I->runDockerComposeCommand('run deploy cloud-deploy'));
        $I->seeInOutput(sprintf(
            '[%d] Variable DATABASE_CONFIGURATION is not configured properly',
            Error::DEPLOY_WRONG_CONFIGURATION_DB
        ));
        $I->seeInOutput('returned non-zero exit status ' . Error::DEPLOY_WRONG_CONFIGURATION_DB);

        $errorLog = $I->grabFileContent('/var/log/cloud.error.log');

        $errors = $this->getErrors($errorLog);

        $I->assertArrayHasKey(Error::WARN_MISSED_MODULE_SECTION, $errors);
        $I->assertArrayHasKey(Error::WARN_CONFIGURATION_STATE_NOT_IDEAL, $errors);
        $I->assertArrayHasKey(Error::DEPLOY_WRONG_CONFIGURATION_DB, $errors);

        $I->runDockerComposeCommand('run deploy ece-command error:show');
        $I->seeInOutput('errorCode: ' . Error::WARN_MISSED_MODULE_SECTION);
        $I->seeInOutput('errorCode: ' . Error::WARN_CONFIGURATION_STATE_NOT_IDEAL);
        $I->seeInOutput('errorCode: ' . Error::DEPLOY_WRONG_CONFIGURATION_DB);
        $I->seeInOutput('type: critical');
    }

    /**
     * @param \CliTester $I
     * @throws \Robo\Exception\TaskException
     */
    public function testDeploySuccess(\CliTester $I): void
    {
        $I->generateDockerCompose('--mode=production');
        $I->copyFileToWorkDir('files/error_codes/.magento.env.success.yaml', '.magento.env.yaml');

        $I->runDockerComposeCommand('run build cloud-build');
        $I->startEnvironment();
        $I->assertTrue($I->runDockerComposeCommand('run deploy cloud-deploy'));
        $I->assertTrue($I->runDockerComposeCommand('run deploy cloud-post-deploy'));
        $I->doNotSeeInOutput('returned non-zero exit status');

        $errorLog = $I->grabFileContent('/var/log/cloud.error.log');

        $errors = $this->getErrors($errorLog);

        $I->assertArrayHasKey(Error::WARN_MISSED_MODULE_SECTION, $errors);
        $I->assertArrayHasKey(Error::WARN_CONFIGURATION_STATE_NOT_IDEAL, $errors);

        $I->runDockerComposeCommand('run deploy ece-command error:show');
        $I->seeInOutput('errorCode: ' . Error::WARN_MISSED_MODULE_SECTION);
        $I->seeInOutput('errorCode: ' . Error::WARN_CONFIGURATION_STATE_NOT_IDEAL);
        $I->doNotSeeInOutput('type: critical');
    }

    /**
     * @param string $errorLog
     * @return array
     */
    private function getErrors(string $errorLog): array
    {
        $errors = [];

        foreach (explode("\n", $errorLog) as $errorLine) {
            $error = json_decode(trim($errorLine), true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                continue;
            }
            $errors[$error['errorCode']] = $error;
        }

        return $errors;
    }
}
