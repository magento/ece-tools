<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Test\Functional\Acceptance;

/**
 * Test for cron:unlock.
 *
 * @group php83
 */
class CronUnlockCest extends AbstractCest
{
    /**
     * @var string
     */
    protected $magentoCloudTemplate = '2.4.7-beta-test';

    /**
     * @param \CliTester $I
     * @throws \Robo\Exception\TaskException
     */
    public function testCronUnlock(\CliTester $I): void
    {
        $I->generateDockerCompose(sprintf('--mode=production --expose-db-port=%s', $I->getExposedPort()));
        $I->runDockerComposeCommand('run build cloud-build');
        $I->startEnvironment();
        $I->runDockerComposeCommand('run deploy cloud-deploy');

        $sampleData = $this->getSampleData();
        $scheduleIds = [];

        foreach ($sampleData as $row) {
            $scheduleIds[] = $I->haveInDatabase('cron_schedule', $row);
        }
        $I->seeInDatabase('cron_schedule', ['status' => 'pending']);

        foreach (array_slice($scheduleIds, 0, 3) as $scheduleId) {
            $this->updateScheduleInDb($I, $scheduleId, 'running');
        }
        $I->seeInDatabase('cron_schedule', ['status' => 'running']);

        $I->assertTrue($I->runDockerComposeCommand('run deploy ece-command cron:unlock'));
        $I->seeInDatabase('cron_schedule', ['status' => 'error']);
        foreach (array_slice($scheduleIds, 0, 3) as $scheduleId) {
            $I->seeInDatabase('cron_schedule', ['schedule_id' => $scheduleId, 'status' => 'error']);
        }

        foreach (array_slice($scheduleIds, 3, 3) as $scheduleId) {
            $this->updateScheduleInDb($I, $scheduleId, 'running');
        }
        $I->seeInDatabase('cron_schedule', ['status' => 'running']);

        $I->assertTrue(
            $I->runDockerComposeCommand(
                sprintf(
                    'run deploy ece-command cron:unlock --job-code=%s --job-code=%s',
                    'catalog_product_frontend_actions_flush',
                    'catalog_product_outdated_price_values_cleanup'
                )
            )
        );

        $I->seeInDatabase('cron_schedule', ['schedule_id' => $scheduleIds[3], 'status' => 'error']);
        $I->seeInDatabase('cron_schedule', ['schedule_id' => $scheduleIds[4], 'status' => 'error']);
        $I->seeInDatabase('cron_schedule', ['schedule_id' => $scheduleIds[5], 'status' => 'running']);
    }

    /**
     * @param \CLITester $I
     * @param $scheduleId
     * @param $status
     */
    private function updateScheduleInDb(\CLITester $I, $scheduleId, $status): void
    {
        $I->updateInDatabase(
            'cron_schedule',
            [
                'status' => $status
            ],
            [
                'schedule_id' => $scheduleId
            ]
        );
    }

    /**
     * @return array
     */
    protected function getSampleData() : array
    {
        return [
            [
                'job_code' => 'update_last_visit_at',
                'status' => 'pending',
                'scheduled_at' => date('Y-m-d h:i:s', strtotime('+1 hours'))
            ],
            [
                'job_code' => 'catalog_product_outdated_price_values_cleanup',
                'status' => 'pending',
                'scheduled_at' => date('Y-m-d h:i:s', strtotime('+2 hours'))
            ],
            [
                'job_code' => 'sales_grid_order_async_insert',
                'status' => 'pending',
                'scheduled_at' => date('Y-m-d h:i:s', strtotime('+3 hours'))
            ],
            [
                'job_code' => 'catalog_product_frontend_actions_flush',
                'status' => 'pending',
                'scheduled_at' => date('Y-m-d h:i:s', strtotime('+4 hours'))
            ],
            [
                'job_code' => 'catalog_product_outdated_price_values_cleanup',
                'status' => 'pending',
                'scheduled_at' => date('Y-m-d h:i:s', strtotime('+5 hours'))
            ],
            [
                'job_code' => 'sales_grid_order_async_insert',
                'status' => 'pending',
                'scheduled_at' => date('Y-m-d h:i:s', strtotime('+6 hours'))
            ]
        ];
    }
}
