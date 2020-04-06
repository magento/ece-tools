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
 * Checks backup databases
 */
class BackupDbCest extends AbstractCest
{
    /**
     * @param CliTester $I
     * @param Example $data
     * @throws TaskException
     * @dataProvider dataProviderTestBackUpDbUnavailable
     */
    public function testBackUpDbUnavailable(CliTester $I, Example $data)
    {
        $I->runEceDockerCommand('build:compose --mode=production');
        $I->runDockerComposeCommand('run build cloud-build');
        $I->runDockerComposeCommand('run deploy cloud-deploy');
        $I->runDockerComposeCommand(
            'run deploy ece-command db-dump '
            . implode(' ', $data['databases'])
        );
        $I->seeInOutput($data['messages']);
    }

    /**
     * @return array
     */
    protected function dataProviderTestBackUpDbUnavailable(): array
    {
        return [
            [
                'databases' => ['quote'],
                'messages' => [
                    'INFO: Starting backup.',
                    'NOTICE: Maintenance mode is disabled.',
                    'CRITICAL: Environment does not have connection `checkout` associated with database `quote`',
                ],
            ],
            [
                'databases' => ['sales'],
                'messages' => [
                    'INFO: Starting backup.',
                    'NOTICE: Maintenance mode is disabled.',
                    'CRITICAL: Environment does not have connection `sales` associated with database `sales`',
                ],
            ],
            [
                'databases' => ['quote', 'sales'],
                'messages' => [
                    'INFO: Starting backup.',
                    'NOTICE: Maintenance mode is disabled.',
                    'CRITICAL: Environment does not have connection `checkout` associated with database `quote`',
                ],
            ]
        ];
    }

    /**
     * @param CliTester $I
     * @throws TaskException
     */
    public function testBackUpDbIncorrect(CliTester $I)
    {
        $I->runEceDockerCommand('build:compose --mode=production');
        $I->startEnvironment();
        $I->runDockerComposeCommand('run build cloud-build');
        $I->runDockerComposeCommand('run deploy ece-command db-dump incorrectName');
        $I->seeInOutput(
            'CRITICAL: Incorrect the database names: [ incorrectName ].'
            . ' Available database names: [ main quote sales ]'
        );
    }

    /**
     * @param CliTester $I
     * @param Example $data
     * @dataProvider dataProvider
     * @throws TaskException
     */
    public function testCreateBackUp(CliTester $I, Example $data)
    {
        $services = $I->readServicesYaml();
        $magentoApp = $I->readAppMagentoYaml();
        foreach ($data['splitDbTypes'] as $splitDbType) {
            $services['mysql-' . $splitDbType]['type'] = 'mysql:10.2';
            $magentoApp['relationships']['database-' . $splitDbType] = 'mysql-' . $splitDbType . ':mysql';
        }
        $I->writeServicesYaml($services);
        $I->writeAppMagentoYaml($magentoApp);
        $I->runEceDockerCommand('build:compose --mode=production');
        $I->startEnvironment();
        $I->runDockerComposeCommand('run build cloud-build');
        $I->runDockerComposeCommand('run deploy cloud-deploy');
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
            $expectedLogs[] = "INFO: Start creation DB dump for {$dbDump} database...";
            $expectedLogs[] = "INFO: Finished DB dump for {$dbDump} database,"
                . " it can be found here: /tmp/dump-{$dbDump}";
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
