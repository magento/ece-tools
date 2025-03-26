<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Test\Functional\Acceptance;

use CliTester;
use Codeception\Example;
use Exception;
use Robo\Exception\TaskException;

/**
 * Checks database backup functionality
 * @group php74
 */
class BackupDb24Cest extends AbstractCest
{
    /**
     * @var array
     */
    private $expectedLogs = [
        'INFO: Starting backup.',
        'NOTICE: Enabling Maintenance mode',
        'INFO: Trying to kill running cron jobs and consumers processes',
        'INFO: Running Magento cron and consumers processes were not found.',
        'INFO: Waiting for lock on db dump.',
        'NOTICE: Maintenance mode is disabled.',
        'INFO: Backup completed.'
    ];

    /**
     * @var array
     */
    private $envMagento = ['stage' => ['global' => ['SCD_ON_DEMAND' => true]]];

    /**
     * {@inheritDoc}
     * @param CliTester $I
     */
    public function _before(CliTester $I): void
    {
        // Do nothing
    }

    /**
     * @param CliTester $I
     * @param Example $data
     * @throws Exception
     * @dataProvider dataProviderMagentoCloudVersions
     */
    public function testBackUpDb(CliTester $I, Example $data): void
    {

        $this->prepareWorkplace($I, $data['version']);

        // Part of test without 'SplitDB' architecture
        $this->partRunDbDumpWithoutSplitDbArch($I);

        $I->stopEnvironment(true);

        // Part of test with 'SplitDB' architecture
        $this->partRunDbDumpWithSplitDbArch($I);
    }

    /**
     * @return array
     */
    protected function dataProviderMagentoCloudVersions(): array
    {
        return [
            ['version' => '2.4.3'],
        ];
    }

    /**
     *  Part of test without 'SplitDB' architecture
     *
     * @param CliTester $I
     * @throws TaskException
     */
    private function partRunDbDumpWithoutSplitDbArch(CliTester $I)
    {
        $I->writeEnvMagentoYaml($this->envMagento);
        $I->generateDockerCompose('--mode=production');

        // Running database dump command with invalid database label
        $I->runDockerComposeCommand('run build cloud-build');

        // Restore app/etc after build phase
        $I->runDockerComposeCommand('run build bash -c "cp -r /app/init/app/etc /app/app"');

        $I->runDockerComposeCommand('run deploy ece-command db-dump incorrectName');
        $I->seeInOutput(
            'CRITICAL: Incorrect the database names: [ incorrectName ].'
            . ' Available database names: [ main quote sales ]'
        );

        // Running database dump command with unavailable database label
        $I->runDockerComposeCommand('run deploy cloud-deploy');

        $I->runDockerComposeCommand('run deploy ece-command db-dump -n quote');
        $I->seeInOutput(
            'CRITICAL: Environment does not have connection `checkout` associated with database `quote`'
        );

        $I->runDockerComposeCommand('run deploy ece-command db-dump -n sales');
        $I->seeInOutput(
            'CRITICAL: Environment does not have connection `sales` associated with database `sales`'
        );

        $I->runDockerComposeCommand('run deploy ece-command db-dump -n quote sales');
        $I->seeInOutput(
            'CRITICAL: Environment does not have connection `checkout` associated with database `quote`'
        );

        // Running database dump command without database label (by default)
        $I->runDockerComposeCommand('run deploy ece-command db-dump -n');
        $I->seeInOutput(array_merge(
            $this->expectedLogs,
            [
                'INFO: Start creation DB dump for main database...',
                'INFO: Finished DB dump for main database, it can be found here: /app/var/dump-main',
            ]
        ));
        $I->doNotSeeInOutput(['quote', 'sales']);
    }

    /**
     * Part of test with 'SplitDB' architecture
     *
     * @param CliTester $I
     * @throws TaskException
     */
    private function partRunDbDumpWithSplitDbArch(CliTester $I)
    {
        // Deploy 'Split Db' architecture
        $services = $I->readServicesYaml();
        $appMagento = $I->readAppMagentoYaml();
        $services['mysql-quote']['type'] = 'mysql:10.2';
        $services['mysql-sales']['type'] = 'mysql:10.2';
        $appMagento['relationships']['database-quote'] = 'mysql-quote:mysql';
        $appMagento['relationships']['database-sales'] = 'mysql-sales:mysql';
        $this->envMagento['stage']['deploy']['SPLIT_DB'] = ['quote', 'sales'];
        $I->writeServicesYaml($services);
        $I->writeAppMagentoYaml($appMagento);
        $I->writeEnvMagentoYaml($this->envMagento);
        $I->generateDockerCompose('--mode=production');
        $I->startEnvironment();
        $I->runDockerComposeCommand('run deploy cloud-deploy');

        // Running database dump command without database labels (by default)
        $I->runDockerComposeCommand('run deploy ece-command db-dump -n');
        $I->seeInOutput(array_merge(
            $this->expectedLogs,
            [
                'INFO: Start creation DB dump for main database...',
                'INFO: Finished DB dump for main database, it can be found here: /app/var/dump-main',
                'INFO: Start creation DB dump for quote database...',
                'INFO: Finished DB dump for quote database, it can be found here: /app/var/dump-quote',
                'INFO: Start creation DB dump for sales database...',
                'INFO: Finished DB dump for sales database, it can be found here: /app/var/dump-sales',
            ]
        ));

        // Running database dump command with database labels
        $I->runDockerComposeCommand('run deploy ece-command db-dump -n quote sales');
        $I->seeInOutput(array_merge(
            $this->expectedLogs,
            [
                'INFO: Start creation DB dump for quote database...',
                'INFO: Finished DB dump for quote database, it can be found here: /app/var/dump-quote',
                'INFO: Start creation DB dump for sales database...',
                'INFO: Finished DB dump for sales database, it can be found here: /app/var/dump-sales',
            ]
        ));
        $I->doNotSeeInOutput('main');
    }
}
