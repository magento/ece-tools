<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Integration;

use Magento\MagentoCloud\Command\Build;
use Magento\MagentoCloud\Command\Deploy;
use Magento\MagentoCloud\DB\ConnectionInterface;
use Magento\MagentoCloud\Filesystem\Driver\File;
use Magento\MagentoCloud\Filesystem\SystemList;
use Magento\MagentoCloud\Shell\ShellInterface;
use Psr\Container\ContainerInterface;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * {@inheritdoc}
 *
 * @group php71
 */
class CronTest extends AbstractTest
{
    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * @var ShellInterface
     */
    private $shell;

    /**
     * @var SystemList
     */
    private $systemList;

    /**
     * @var ConnectionInterface
     */
    private $connection;

    /**
     * @var File
     */
    private $file;

    /**
     * @inheritdoc
     */
    public function setUp()
    {
        parent::setUp();

        $this->container = Bootstrap::getInstance()->createApplication()->getContainer();
        $this->shell = $this->container->get(ShellInterface::class);
        $this->systemList = $this->container->get(SystemList::class);
        $this->connection = $this->container->get(ConnectionInterface::class);
        $this->file = $this->container->get(File::class);
    }

    /**
     * @inheritdoc
     */
    public static function setUpBeforeClass()
    {
        Bootstrap::getInstance()->run('2.2.0');
    }

    /**
     * @param string $version
     * @dataProvider cronDataProvider
     */
    public function testCron(string $version)
    {
        $this->updateToVersion($version);

        $this->file->createDirectory(sprintf(
            '%s/app/code/Magento/CronTest',
            $this->systemList->getMagentoRoot()
        ));
        $this->file->copyDirectory(
            sprintf('%s/_files/modules/Magento/CronTest', __DIR__),
            sprintf('%s/app/code/Magento/CronTest', $this->systemList->getMagentoRoot())
        );

        $this->executeAndAssert(Build::NAME);
        $this->executeAndAssert(Deploy::NAME);

        $this->connection->close();

        $this->assertTrue($this->connection->query('DELETE FROM `cron_schedule`;'));

        $this->shell->execute('php ./bin/magento cron:run && php ./bin/magento cron:run');

        $selectSuccessJobs = 'SELECT * FROM cron_schedule WHERE job_code = "cron_test_job" AND status = "success"';
        $updatePendingJobs = 'UPDATE cron_schedule SET scheduled_at = NOW() '
            . 'WHERE job_code = "cron_test_job" AND status = "pending"';
        $addRunningJob = 'INSERT INTO cron_schedule '
            . 'SET job_code = "cron_test_job", status = "running", created_at = NOW() - INTERVAL 3 minute, '
            . 'scheduled_at = NOW() - INTERVAL 2 minute, executed_at = NOW() - INTERVAL 2 minute';
        $updateRunningJob = 'UPDATE cron_schedule '
            . 'SET created_at = NOW() - INTERVAL 3 day, scheduled_at = NOW() - INTERVAL 2 day, '
            . 'executed_at = NOW() - INTERVAL 2 day WHERE job_code = "cron_test_job" AND status = "running"';
        $countSuccess = $this->connection->count($selectSuccessJobs);

        $this->assertTrue($this->connection->query($addRunningJob));
        $this->assertTrue($this->connection->query($updatePendingJobs));

        $this->shell->execute('php ./bin/magento cron:run');

        $this->assertSame($countSuccess, $this->connection->count($selectSuccessJobs));
        $this->assertTrue($this->connection->query($updateRunningJob));
        $this->assertTrue($this->connection->query($updatePendingJobs));

        $this->shell->execute('php ./bin/magento cron:run');

        $this->assertTrue($countSuccess < $this->connection->count($selectSuccessJobs));

        $this->connection->close();
    }

    /**
     * @param string $commandName
     * @return void
     */
    private function executeAndAssert($commandName)
    {
        $application = Bootstrap::getInstance()->createApplication();
        $commandTester = new CommandTester($application->get($commandName));
        $commandTester->execute([]);

        $this->assertSame(0, $commandTester->getStatusCode());
    }

    /**
     * @param string $version
     */
    private function updateToVersion(string $version)
    {
        $this->shell->execute('rm -rf ./vendor/*');
        $this->shell->execute(sprintf(
            'composer require magento/product-enterprise-edition %s --no-update -n',
            $version
        ));
        $this->shell->execute('composer update -n');
    }

    /**
     * @return array
     */
    public function cronDataProvider(): array
    {
        return [
            ['version' => '2.2.0'],
            ['version' => '2.2.2'],
            ['version' => '@stable'],
        ];
    }
}
