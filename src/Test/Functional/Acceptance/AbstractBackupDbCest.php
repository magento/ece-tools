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
use Exception;

/**
 * General Cest for Backup Db tests
 */
class AbstractBackupDbCest extends AbstractCest
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
     * {@inheritDoc}
     * @param CliTester $I
     */
    public function _after(CliTester $I): void
    {
        // Do nothing
    }

    /**
     * @var integer
     */
    private static $counter = 0;

    /**
     * @var boolean
     */
    private static $beforeShouldRun = true;

    /**
     * @param CliTester $I
     * @param string $version
     * @throws TaskException
     */
    private function internalBefore(CliTester $I, string $version): void
    {
        if (self::$beforeShouldRun) {
            $this->prepareWorkplace($I, $version);
            $I->runEceDockerCommand('build:compose --mode=production');
            $I->runDockerComposeCommand('run build cloud-build');
            $I->runDockerComposeCommand('run deploy cloud-deploy');
            self::$beforeShouldRun = false;
        }

        self::$counter++;
    }

    /**
     * @param CliTester $I
     */
    private function internalAfter(CliTester $I): void
    {
        self::$beforeShouldRun = true;
        self::$counter = 0;
        parent::_after($I);
    }

    /**
     * @param CliTester $I
     * @param Example $data
     * @throws Exception
     * @dataProvider dataProviderBackUpDbUnavailable
     */
    public function testBackUpDbUnavailable(CliTester $I, Example $data)
    {
        try {
            $this->internalBefore($I, $data['version']);
            $I->runDockerComposeCommand(
                'run deploy ece-command db-dump '
                . implode(' ', $data['databases'])
            );
            $I->seeInOutput([
                'INFO: Starting backup.',
                'NOTICE: Maintenance mode is disabled.',
                $data['message']
            ]);
        } catch (Exception $exception) {
            $this->internalAfter($I);
            throw $exception;
        }
        if (self::$counter === $this->dataProviderBackUpDbUnavailable()) {
            $this->internalAfter($I);
        }
    }

    /**
     * @return array
     */
    protected function dataProviderBackUpDbUnavailable(): array
    {
        return [
            [
                'databases' => ['quote'],
                'message' => 'CRITICAL: Environment does not have connection'
                    . ' `checkout` associated with database `quote`',
                'version' => 'master',
            ],
            [
                'databases' => ['sales'],
                'message' => 'CRITICAL: Environment does not have connection'
                    . ' `sales` associated with database `sales`',
                'version' => 'master',
            ],
            [
                'databases' => ['quote', 'sales'],
                'message' => 'CRITICAL: Environment does not have connection'
                    . ' `checkout` associated with database `quote`',
                'version' => 'master',
            ]
        ];
    }

    /**
     * @param CliTester $I
     * @param Example $data
     * @throws Exception
     * @dataProvider dataProviderBackUpDbIncorrect
     */
    public function testBackUpDbIncorrect(CliTester $I, Example $data)
    {
        try {
            $this->internalBefore($I, $data['version']);
            $I->runDockerComposeCommand('run deploy ece-command db-dump incorrectName');
            $I->seeInOutput(
                'CRITICAL: Incorrect the database names: [ incorrectName ].'
                . ' Available database names: [ main quote sales ]'
            );
        } finally {
            $this->internalAfter($I);
        }
    }

    protected function dataProviderBackUpDbIncorrect(): array
    {
        return [
            ['version' => 'master']
        ];
    }

    /**
     * @param CliTester $I
     * @param Example $data
     * @dataProvider dataProviderCreateBackUp
     * @throws Exception
     */
    public function testCreateBackUp(CliTester $I, Example $data)
    {
        if (self::$beforeShouldRun) {
            $this->prepareWorkplace($I, $data['version']);
            self::$beforeShouldRun = false;
        }

        self::$counter++;

        try {
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
            $I->stopEnvironment();
        } catch (Exception $exception) {
            self::$beforeShouldRun = true;
            self::$counter = 0;
            parent::_after($I);
            throw $exception;
        }
        if (self::$counter === $this->dataProviderCreateBackUp()) {
            self::$beforeShouldRun = true;
            self::$counter = 0;
            parent::_after($I);
        }
    }

    /**
     * @return array
     */
    protected function dataProviderCreateBackUp(): array
    {
        return [
            [
                'splitDbTypes' => [],
                'databases' => [],
                'dbDumps' => ['main'],
                'version' => 'master',
            ],
            [
                'splitDbTypes' => ['quote', 'sales'],
                'databases' => [],
                'dbDumps' => ['main', 'quote', 'sales'],
                'version' => 'master',
            ],
            [
                'splitDbTypes' => ['quote', 'sales'],
                'databases' => ['main', 'sales'],
                'dbDumps' => ['main', 'sales'],
                'version' => 'master',
            ]
        ];
    }
}
