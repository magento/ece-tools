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
class CronCest extends AbstractCest
{
    /**
     * @param \CliTester $I
     * @param \Codeception\Example $data
     * @throws \Codeception\Exception\ModuleException
     * @throws \Robo\Exception\TaskException
     * @dataProvider cronDataProvider
     */
    public function testCron(\CliTester $I, \Codeception\Example $data)
    {
        $I->assertTrue($I->cloneTemplate($data['version']));
        $I->assertTrue($I->addEceComposerRepo());
        $I->createDirectory('/app/code/Magento/CronTest', Docker::BUILD_CONTAINER);
        $I->uploadToContainer('modules/Magento/CronTest/.', '/app/code/Magento/CronTest', Docker::BUILD_CONTAINER);
        $I->assertTrue($I->runEceToolsCommand('build', Docker::BUILD_CONTAINER, $data['variables']));
        $I->startEnvironment();
        $I->assertTrue($I->runEceToolsCommand('deploy', Docker::DEPLOY_CONTAINER, $data['variables']));
        $I->assertTrue($I->runEceToolsCommand('post-deploy', Docker::DEPLOY_CONTAINER, $data['variables']));
        $I->deleteFromDatabase('cron_schedule');
        $I->assertTrue($I->runBinMagentoCommand('cron:run', Docker::DEPLOY_CONTAINER));

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

        $I->assertTrue($I->runBinMagentoCommand('cron:run', Docker::DEPLOY_CONTAINER));

        $successfulJobs2 = $I->grabNumRecords('cron_schedule', ['job_code' => 'cron_test_job', 'status' => 'success']);
        $I->assertEquals($successfulJobs1, $successfulJobs2, 'Number of successful jobs changed');

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

        $I->assertTrue($I->runBinMagentoCommand('cron:run', Docker::DEPLOY_CONTAINER));

        $successfulJobs3 = $I->grabNumRecords('cron_schedule', ['job_code' => 'cron_test_job', 'status' => 'success']);
        $I->assertGreaterThan($successfulJobs1, $successfulJobs3, 'Number of successful jobs did not change');
    }

    /**
     * @param \CliTester $I
     * @param string $jobCode
     * @param int $timeInterval
     * @throws \Exception
     */
    private function checkCronJobForLocale(\CliTester $I, string $jobCode, int $timeInterval)
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
                $I->assertEquals($timeInterval, $diff->i, 'Schedule is not ' . $timeInterval . ' minutes apart');
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
                'version' => '2.3.1',
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
