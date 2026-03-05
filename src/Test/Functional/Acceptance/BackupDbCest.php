<?php
declare(strict_types=1);

namespace Magento\MagentoCloud\Test\Functional\Acceptance;

use CliTester;
use Codeception\Example;
use Exception;
use Robo\Exception\TaskException;

/**
 * Checks database backup functionality
 */
abstract class BackupDbCest extends AbstractCest
{
    /**
     * Expected log lines for a successful `ece-command db-dump -n` run.
     *
     * @var string[]
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
     * Magento Cloud env configuration used for this test.
     *
     * @var array
     */
    private $envMagento = ['stage' => ['global' => ['SCD_ON_DEMAND' => true]]];

    /**
     * Before hook to prepare test.
     *
     * @param CliTester $I
     * @return void
     */
    public function _before(CliTester $I): void
    {
        // Do nothing
    }

    /**
     * Test database backup functionality.
     *
     * @dataProvider dataProviderMagentoCloudVersions
     * @return void
     * @throws Exception
     */
    public function testBackUpDb(CliTester $I, Example $data): void
    {
        $this->prepareWorkplace($I, $data['version']);
        $this->partRunDbDumpWithoutSplitDbArch($I);
        $I->stopEnvironment(true);
    }

    abstract protected function dataProviderMagentoCloudVersions(): array;

    /**
     * Part of test without 'SplitDB' architecture.
     *
     * @return void
     * @throws TaskException
     */
    private function partRunDbDumpWithoutSplitDbArch(CliTester $I): void
    {
        $I->writeEnvMagentoYaml($this->envMagento);
        $I->generateDockerCompose('--mode=production');
        $this->removeVendorVolumeMountFromDockerCompose($I);

        // Build phase
        $I->runDockerComposeCommand('run build cloud-build');

        // Restore app/etc after build phase
        $I->runDockerComposeCommand('run build bash -c "cp -r /app/init/app/etc /app/app"');

        // Deploy phase
        $I->runDockerComposeCommand('run deploy cloud-deploy');

        // Invalid database name
        $I->assertFalse(
            $this->runDbDumpCommand($I, 'incorrectName'),
            'db-dump with invalid database name should fail'
        );
        $I->seeInOutput('CRITICAL: Incorrect the database names: [ incorrectName ].');
        $I->seeInOutput('Available database names:');
        $I->seeInOutput(['main', 'quote', 'sales']);

        // Unavailable database labels
        $I->assertFalse(
            $this->runDbDumpCommand($I, '-n quote'),
            'db-dump for unavailable database label should fail'
        );
        $I->seeInOutput('CRITICAL: Environment does not have connection `checkout` associated with database `quote`');

        $I->assertFalse(
            $this->runDbDumpCommand($I, '-n sales'),
            'db-dump for unavailable database label should fail'
        );
        $I->seeInOutput('CRITICAL: Environment does not have connection `sales` associated with database `sales`');

        $I->assertFalse(
            $this->runDbDumpCommand($I, '-n quote sales'),
            'db-dump for unavailable database labels should fail'
        );
        $I->seeInOutput('CRITICAL: Environment does not have connection `checkout` associated with database `quote`');

        // Default DB dump (should succeed)
        $I->assertTrue(
            $this->runDbDumpCommand($I, '-n'),
            'db-dump without database label should succeed'
        );

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
     * Run the database dump command with specified arguments.
     *
     * @return bool
     */
    private function runDbDumpCommand(CliTester $I, string $args): bool
    {
        $args = trim($args);
        $command = 'set -e; '
            . 'if [ -x /usr/bin/mysqldump ] && [ ! -x /usr/bin/mysqldump.orig ]; '
            . 'then mv /usr/bin/mysqldump /usr/bin/mysqldump.orig; fi; '
            . 'mkdir -p /usr/local/bin; '
            . 'printf "%s\n" '
            . '"#!/usr/bin/env bash" '
            . '"real=/usr/bin/mysqldump.orig" '
            . '"if [ ! -x \"\\$real\" ]; then" '
            . '"  echo \"Missing mysqldump binary at \\$real\" 1>&2" '
            . '"  exit 127" '
            . '"fi" '
            . '"opt=\"--ssl=0\"" '
            . '"if \"\\$real\" --help 2>&1 | grep -q -- \"--ssl-mode\"; then" '
            . '"  opt=\"--ssl-mode=DISABLED\"" '
            . '"elif \"\\$real\" --help 2>&1 | grep -q -- \"--skip-ssl\"; then" '
            . '"  opt=\"--skip-ssl\"" '
            . '"fi" '
            . '"exec \"\\$real\" \"\\$opt\" \"\\$@\"" '
            . '> /usr/local/bin/mysqldump; '
            . 'chmod +x /usr/local/bin/mysqldump; '
            . 'ln -sf /usr/local/bin/mysqldump /usr/bin/mysqldump; '
            . 'exec ece-command db-dump' . ($args === '' ? '' : ' ' . $args);

        $dockerCommand = "run --user root deploy bash -lc '" . $command . "'";

        return $I->runDockerComposeCommand($dockerCommand);
    }
}
