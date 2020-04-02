<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Test\Functional\Acceptance;

use CliTester;
use Codeception\Example;
use Robo\Exception\TaskException;

/**
 * Test of backup databases
 */
class BackupDbCest extends AbstractCest
{
    /**
     * @param CliTester $I
     */
    public function testBackUpDbUnavailable(CliTester $I)
    {
        $I->runDockerComposeCommand('run deploy ece-command db-dump quote');
        $I->seeInOutput([
            'INFO: Starting backup.',
            'NOTICE: Maintenance mode is disabled.',
            'CRITICAL: Environment does not have connection `checkout` associated with database `quote`',
        ]);
    }

    /**
     * @param CliTester $I
     */
    public function testBackUpDbIncorrect(CliTester $I)
    {
        $I->runDockerComposeCommand('run deploy ece-command db-dump incorrectName');
        $I->seeInOutput(
            'CRITICAL: Incorrect the database names: [ incorrectName ]. Available database names: [ main quote sales ]'
        );
    }

    /**
     * @param CliTester $I
     * @param Example $data
     * @dataProvider dataProvider
     * @throws TaskException
     */
    public function testBackUp(CliTester $I, Example $data)
    {
        $services = $I->readServicesYaml();
        $magentoApp = $I->readAppMagentoYaml();
        $envMagentoYamlData = ['stage' => ['global' => ['SCD_ON_DEMAND' => true]]];
        $buildComposeCommand = 'build:compose --mode=production --expose-db-port=' . $I->getExposedPort();
        foreach ($data['splitDbTypes'] as $splitDbType) {
            $services['mysql-' . $splitDbType]['type'] = 'mysql:10.2';
            $magentoApp['relationships']['database-' . $splitDbType] = 'mysql-' . $splitDbType . ':mysql';
            $envMagentoYamlData['stage']['deploy']['SPLIT_DB'][] = $splitDbType;
            $buildComposeCommand .= sprintf(
                ' --expose-db-%s-port=%s',
                $splitDbType,
                $I->getExposedPort('db_' . $splitDbType)
            );
        }
        $I->writeEnvMagentoYaml($envMagentoYamlData);
        $I->writeServicesYaml($services);
        $I->writeAppMagentoYaml($magentoApp);
        $I->runEceDockerCommand($buildComposeCommand);
        $I->startEnvironment();
        $I->runDockerComposeCommand('run build cloud-build');
        $I->runDockerComposeCommand('run deploy cloud-deploy');
        $I->runDockerComposeCommand('run deploy cloud-post-deploy');

        $dumpCommand = 'run deploy /bin/bash -c \'ece-command db-dump';
        foreach ($data['databases'] as $database) {
            $dumpCommand .= ' ' . $database;
        }
        $I->runDockerComposeCommand($dumpCommand . ' && mv /tmp/dump-*.sql.gz $MAGENTO_ROOT/var/\'');
        $expectedDumpList = [];
        $expectedLogs = [
            'INFO: Starting backup.',
            'NOTICE: Enabling Maintenance mode',
            'INFO: Trying to kill running cron jobs and consumers processes',
            'INFO: Running Magento cron and consumers processes were not found.',
            'INFO: Waiting for lock on db dump.',
        ];

        foreach ($data['dbDumps'] as $dbDump) {
            $expectedLogs[] = "INFO: Start creation DB dump for {$dbDump} database...  ";
            $expectedLogs[] = "INFO: Finished DB dump for main database, it can be found here: /tmp/dump-{$dbDump}";
            $expectedDumpList[] = 'dump-' . $dbDump;
        }

        $expectedLogs[] = 'NOTICE: Maintenance mode is disabled.';
        $expectedLogs[] = 'INFO: Backup completed.';
        $I->seeInOutput($expectedLogs);
        $I->runDockerComposeCommand('run deploy bash -c \'ls -al $MAGENTO_ROOT/var/dump-*.sql.gz\'');
        $I->seeInOutput($expectedDumpList);
    }

    protected function dataProvider(): array
    {
        return [
            [
                'splitDbTypes' => [],
                'databases' => [],
                'dbDumps' => ['main'],
            ],
            [
                'splitDbTypes' => ['quote', 'sales'],
                'databases' => [],
                'dbDumps' => ['main', 'quote', 'sales'],
            ],
            [
                'splitDbTypes' => ['quote', 'sales'],
                'databases' => ['main', 'sales'],
                'dbDumps' => ['main', 'sales'],
            ]
        ];
    }
}
