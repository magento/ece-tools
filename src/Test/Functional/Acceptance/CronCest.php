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
class CronCest extends AbstractCest
{
    /**
     * @inheritdoc
     */
    public function _before(\CliTester $I): void
    {
        //Do nothing...
    }

    /**
     * @param \CliTester $I
     * @param \Codeception\Example $data
     * @throws \Codeception\Exception\ModuleException
     * @throws \Robo\Exception\TaskException
     * @dataProvider cronDataProvider
     */
    public function testCron(\CliTester $I, \Codeception\Example $data): void
    {
        $this->prepareWorkplace($I, $data['version']);
        $I->generateDockerCompose(sprintf(
            '--mode=production --expose-db-port=%s --env-vars="%s"',
            $I->getExposedPort(),
            $this->convertEnvFromArrayToJson($data['variables'])
        ));
        $I->copyDirToWorkDir('modules/Magento/CronTest', 'app/code/Magento/CronTest');
        $I->runDockerComposeCommand('run build cloud-build');
        $I->startEnvironment();
        $I->runDockerComposeCommand('run deploy cloud-deploy');
        $I->runDockerComposeCommand('run deploy cloud-post-deploy');
        $I->deleteFromDatabase('cron_schedule');
        $I->runDockerComposeCommand('run deploy magento-command cron:run');
        $I->runDockerComposeCommand('run deploy magento-command cron:run');

        $this->checkCronJobForLocale($I, 'cron_test_job_timeformat', 5);
        $this->checkCronJobForLocale($I, 'cron_test_job_timeformat_six', 6);

        $successfulJobs1 = $I->grabNumRecords('cron_schedule', ['job_code' => 'cron_test_job', 'status' => 'success']);
        $I->assertGreaterThan(0, $successfulJobs1, 'No successful cron jobs');

        $I->haveInDatabase('cron_schedule', [
            'job_code' => 'cron_test_job',
            'status' => 'running',
            'created_at' => date('Y-m-d H:i:s', strtotime('-3 minutes')),
            'scheduled_at' => date('Y-m-d H:i:s', strtotime('-2 minutes')),
            'executed_at' => date('Y-m-d H:i:s', strtotime('-2 minutes')),
        ]);

        $I->updateInDatabase('cron_schedule', ['scheduled_at' => date('Y-m-d H:i:s')], ['status' => 'pending']);

        $I->runDockerComposeCommand('run deploy magento-command cron:run');
        $I->runDockerComposeCommand('run deploy magento-command cron:run');

        $successfulJobs2 = $I->grabNumRecords('cron_schedule', ['job_code' => 'cron_test_job', 'status' => 'success']);

        if (version_compare($data['version'], '2.2.5', '<')) {
            $I->assertEquals($successfulJobs1, $successfulJobs2, 'Number of successful jobs changed');
        } else {
            $I->assertGreaterThan($successfulJobs1, $successfulJobs2, 'Number of successful jobs did not change');
        }

        $I->updateInDatabase(
            'cron_schedule',
            [
                'created_at' => date('Y-m-d H:i:s', strtotime('-3 days')),
                'scheduled_at' => date('Y-m-d H:i:s', strtotime('-2 days')),
                'executed_at' => date('Y-m-d H:i:s', strtotime('-2 days')),
            ],
            ['job_code' => 'cron_test_job', 'status' => 'running']
        );

        $I->updateInDatabase(
            'cron_schedule',
            ['scheduled_at' => date('Y-m-d H:i:s')],
            ['job_code' => 'cron_test_job', 'status' => 'pending']
        );

        $I->runDockerComposeCommand('run deploy magento-command cron:run');

        $successfulJobs3 = $I->grabNumRecords('cron_schedule', ['job_code' => 'cron_test_job', 'status' => 'success']);
        $I->assertGreaterThan(
            version_compare($data['version'], '2.2.5', '<') ? $successfulJobs1 : $successfulJobs2,
            $successfulJobs3,
            'Number of successful jobs did not change'
        );
    }

    /**
     * @param \CliTester $I
     * @param string $jobCode
     * @param int $timeInterval
     * @throws \Exception
     */
    private function checkCronJobForLocale(\CliTester $I, string $jobCode, int $timeInterval): void
    {
        $schedule = $I->grabColumnFromDatabase(
            'cron_schedule',
            'scheduled_at',
            ['job_code' => $jobCode]
        );

        $previousTimestamp = null;

        foreach ($schedule as $timestamp) {
            $timestamp = new \DateTime($timestamp);

            if (isset($previousTimestamp)) {
                $diff = $timestamp->diff($previousTimestamp);
                $I->assertContains(
                    $diff->i,
                    [$timeInterval, $timeInterval*3],
                    'Schedule is not ' . $timeInterval . '/' . $timeInterval*3 . ' minutes apart'
                );
            }

            $previousTimestamp = $timestamp;
        }
    }

    /**
     * @return array
     */
    protected function cronDataProvider(): array
    {
        return [
            [
                'version' => '2.4.7-beta-test',
                'variables' => [
                    'MAGENTO_CLOUD_VARIABLES' => [
                        'ADMIN_EMAIL' => 'admin@example.com',
                        'ADMIN_LOCALE' => 'fr_FR'
                    ],
                ],
            ],
        ];
    }
}
