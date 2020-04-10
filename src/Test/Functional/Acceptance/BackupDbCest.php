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
 */
class BackupDbCest extends AbstractCest
{
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
     * @dataProvider dataProviderVersions
     */
    public function testBackUpDbUnavailable(CliTester $I, Example $data)
    {
        $this->deploy($I, $data['version']);
        $I->runDockerComposeCommand('run deploy ece-command db-dump quote');
        $I->seeInOutput([
            'INFO: Starting backup.',
            'NOTICE: Maintenance mode is disabled.',
            'CRITICAL: Environment does not have connection `checkout` associated with database `quote`'
        ]);
        $I->runDockerComposeCommand('run deploy ece-command db-dump sales');
        $I->seeInOutput([
            'INFO: Starting backup.',
            'NOTICE: Maintenance mode is disabled.',
            'CRITICAL: Environment does not have connection `sales` associated with database `sales`'
        ]);
        $I->runDockerComposeCommand('run deploy ece-command db-dump quote sales');
        $I->seeInOutput([
            'INFO: Starting backup.',
            'NOTICE: Maintenance mode is disabled.',
            'CRITICAL: Environment does not have connection `checkout` associated with database `quote`'
        ]);
    }

    /**
     * @param CliTester $I
     * @throws Exception
     */
    public function testBackUpDbIncorrect(CliTester $I)
    {
        $this->prepareWorkplace($I, 'master');
        $I->writeEnvMagentoYaml(['stage' => ['global' => ['SCD_ON_DEMAND' => true]]]);
        $I->runEceDockerCommand('build:compose --mode=production');
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
     * @dataProvider dataProviderVersions
     * @throws Exception
     */
    public function testCreateBackUp(CliTester $I, Example $data)
    {
        $dumpDbCommand = 'run deploy ece-command db-dump';
        $expectedLogs = [
            'INFO: Starting backup.',
            'NOTICE: Enabling Maintenance mode',
            'INFO: Trying to kill running cron jobs and consumers processes',
            'INFO: Running Magento cron and consumers processes were not found.',
            'INFO: Waiting for lock on db dump.',
            'NOTICE: Maintenance mode is disabled.',
            'INFO: Backup completed.'
        ];

        $this->deploy($I, $data['version']);

        $I->runDockerComposeCommand($dumpDbCommand);
        $I->seeInOutput(array_merge(
            $expectedLogs,
            [
                'INFO: Start creation DB dump for main database...',
                'INFO: Finished DB dump for main database, it can be found here: /tmp/dump-main',
            ]
        ));
        $I->doNotSeeInOutput(['quote', 'sales']);

        $services = $I->readServicesYaml();
        $magentoApp = $I->readAppMagentoYaml();
        $services['mysql-quote']['type'] = 'mysql:10.2';
        $services['mysql-sales']['type'] = 'mysql:10.2';
        $magentoApp['relationships']['database-quote'] = 'mysql-quote:mysql';
        $magentoApp['relationships']['database-sales'] = 'mysql-sales:mysql';
        $I->writeServicesYaml($services);
        $I->writeAppMagentoYaml($magentoApp);
        $I->runEceDockerCommand('build:compose --mode=production');
        $I->startEnvironment();
        $I->runDockerComposeCommand($dumpDbCommand);
        $I->seeInOutput(array_merge(
            $expectedLogs,
            [
                'INFO: Start creation DB dump for main database...',
                'INFO: Finished DB dump for main database, it can be found here: /tmp/dump-main',
                'INFO: Start creation DB dump for quote database...',
                'INFO: Finished DB dump for quote database, it can be found here: /tmp/dump-quote',
                'INFO: Start creation DB dump for sales database...',
                'INFO: Finished DB dump for sales database, it can be found here: /tmp/dump-sales',
            ]
        ));

        $I->runDockerComposeCommand($dumpDbCommand . ' quote sales');
        $I->seeInOutput(array_merge(
            $expectedLogs,
            [
                'INFO: Start creation DB dump for quote database...',
                'INFO: Finished DB dump for quote database, it can be found here: /tmp/dump-quote',
                'INFO: Start creation DB dump for sales database...',
                'INFO: Finished DB dump for sales database, it can be found here: /tmp/dump-sales',
            ]
        ));
        $I->doNotSeeInOutput('main');
    }

    /**
     * @return array
     */
    protected function dataProviderVersions(): array
    {
        return [
            ['version' => 'master'],
            ['version' => '2.3.4'],
            ['version' => '2.2.11'],
            ['version' => '2.1.18'],
        ];
    }

    /**
     * @param CliTester $I
     * @param string $version
     * @throws TaskException
     */
    private function deploy(CliTester $I, string $version)
    {
        $this->prepareWorkplace($I, $version);
        $I->writeEnvMagentoYaml(['stage' => ['global' => ['SCD_ON_DEMAND' => true]]]);
        $I->runEceDockerCommand('build:compose --mode=production');
        $I->runDockerComposeCommand('run build cloud-build');
        $I->runDockerComposeCommand('run deploy cloud-deploy');
    }
}
