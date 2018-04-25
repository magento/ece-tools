<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Integration;

use Magento\MagentoCloud\Command\Build;
use Magento\MagentoCloud\Command\Deploy;
use Magento\MagentoCloud\Application;
use Magento\MagentoCloud\DB\ConnectionInterface;
use Magento\MagentoCloud\Filesystem\Driver\File;
use Magento\MagentoCloud\Shell\ShellInterface;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * {@inheritdoc}
 *
 * @group php71
 */
class CronTest extends AbstractTest
{
    /**
     * @var ShellInterface
     */
    private $shell;

    /**
     * @var ConnectionInterface
     */
    private $connection;

    /**
     * @param string $commandName
     * @param Application $application
     * @return void
     */
    private function executeAndAssert($commandName, $application)
    {
        $commandTester = new CommandTester($application->get($commandName));
        $commandTester->execute([]);

        $this->assertSame(0, $commandTester->getStatusCode());
    }

    /**
     * @inheritdoc
     */
    public static function setUpBeforeClass()
    {
    }

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        $container = Bootstrap::getInstance()
            ->createApplication()
            ->getContainer();

        $this->shell = $container->get(ShellInterface::class);
        $this->connection = $container->get(ConnectionInterface::class);
    }

    /**
     * @param string $version
     * @dataProvider cronDataProvider
     */
    public function testCron($version = null)
    {
        $bootstrap = Bootstrap::getInstance();

        $bootstrap->run($version);
        $bootstrap->execute(sprintf(
            'cd %s && composer install -n --no-dev --no-progress',
            $this->bootstrap->getSandboxDir()
        ));

        $application = $bootstrap->createApplication(['variables' => ['ADMIN_EMAIL' => 'admin@example.com']]);

        /** @var File $file */
        $file = $application->getContainer()->get(File::class);
        $file->createDirectory(sprintf(
            '%s/app/code/Magento/CronTest',
            $bootstrap->getSandboxDir()
        ));
        $file->copyDirectory(
            sprintf('%s/_files/modules/Magento/CronTest', __DIR__),
            sprintf('%s/app/code/Magento/CronTest', $bootstrap->getSandboxDir())
        );

        $this->executeAndAssert(Build::NAME, $application);
        $this->executeAndAssert(Deploy::NAME, $application);

        /** @var ConnectionInterface $db */
        $db = $application->getContainer()->get(ConnectionInterface::class);
        $db->close();

        $this->assertTrue($db->query('DELETE FROM cron_schedule;'));
        $bootstrap->execute(sprintf(
            'cd %s && php bin/magento cron:run && php bin/magento cron:run',
            $bootstrap->getSandboxDir()
        ));

        $selectSuccessJobs = 'SELECT * FROM cron_schedule WHERE job_code = "cron_test_job" AND status = "success"';
        $updatePendingJobs = 'UPDATE cron_schedule SET scheduled_at = NOW() '
            . 'WHERE job_code = "cron_test_job" AND status = "pending"';
        $addRunningJob = 'INSERT INTO cron_schedule '
            . 'SET job_code = "cron_test_job", status = "running", created_at = NOW() - INTERVAL 3 minute, '
            . 'scheduled_at = NOW() - INTERVAL 2 minute, executed_at = NOW() - INTERVAL 2 minute';
        $updateRunningJob = 'UPDATE cron_schedule '
            . 'SET created_at = NOW() - INTERVAL 3 day, scheduled_at = NOW() - INTERVAL 2 day, '
            . 'executed_at = NOW() - INTERVAL 2 day WHERE job_code = "cron_test_job" AND status = "running"';

        $countSuccess = count($db->select($selectSuccessJobs));
        $this->assertTrue($db->query($addRunningJob));
        $this->assertTrue($db->query($updatePendingJobs));

        $bootstrap->execute(sprintf(
            'cd %s && php bin/magento cron:run',
            $bootstrap->getSandboxDir()
        ));

        $this->assertCount($countSuccess, $db->select($selectSuccessJobs));
        $this->assertTrue($db->query($updateRunningJob));
        $this->assertTrue($db->query($updatePendingJobs));

        $bootstrap->execute(sprintf(
            'cd %s && php bin/magento cron:run',
            $bootstrap->getSandboxDir()
        ));

        $this->assertTrue($countSuccess < count($db->select($selectSuccessJobs)));

        $db->close();
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

    /**
     * @inheritdoc
     */
    protected function tearDown()
    {
        parent::tearDown();

        Bootstrap::getInstance()->destroy();
    }
}
