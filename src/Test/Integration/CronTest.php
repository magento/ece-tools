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
use Symfony\Component\Console\Tester\CommandTester;

/**
 * {@inheritdoc}
 *
 * @group php71
 */
class CronTest extends AbstractTest
{
    /**
     * @param string $commandName
     * @param Application $application
     * @return void
     */
    private function executeAndAssert($commandName, $application)
    {
        $application->getContainer()->set(
            \Psr\Log\LoggerInterface::class,
            \Magento\MagentoCloud\App\Logger::class
        );
        $commandTester = new CommandTester($application->get($commandName));
        $commandTester->execute([]);
        $this->assertSame(0, $commandTester->getStatusCode());
    }

    /**
     * @inheritdoc
     */
    public static function setUpBeforeClass()
    {
        //Do nothing for this test...
    }

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        $this->bootstrap = Bootstrap::getInstance();
    }

    /**
     * @param string $version
     * @dataProvider cronDataProvider
     */
    public function testCron($version = null)
    {
        $this->bootstrap->run($version);
        $this->bootstrap->execute(sprintf(
            'cd %s && composer install -n --no-dev --no-progress',
            $this->bootstrap->getSandboxDir()
        ));

        $application = $this->bootstrap->createApplication(['variables' => ['ADMIN_EMAIL' => 'admin@example.com']]);

        /** @var File $file */
        $file = $application->getContainer()->get(File::class);
        $file->createDirectory(sprintf(
            '%s/app/code/Magento/CronTest',
            $this->bootstrap->getSandboxDir()
        ));
        $file->copyDirectory(
            sprintf('%s/_files/modules/Magento/CronTest', __DIR__),
            sprintf('%s/app/code/Magento/CronTest', $this->bootstrap->getSandboxDir())
        );

        $this->executeAndAssert(Build::NAME, $application);
        $this->executeAndAssert(Deploy::NAME, $application);

        /** @var ConnectionInterface $db */
        $db = $application->getContainer()->get(ConnectionInterface::class);
        $db->close();

        $this->assertTrue($db->query('DELETE FROM cron_schedule;'));
        $this->bootstrap->execute(sprintf(
            'cd %s && php bin/magento cron:run && php bin/magento cron:run',
            $this->bootstrap->getSandboxDir()
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

        $this->bootstrap->execute(sprintf(
            'cd %s && php bin/magento cron:run',
            $this->bootstrap->getSandboxDir()
        ));

        $this->assertTrue($countSuccess == count($db->select($selectSuccessJobs)));

        $this->assertTrue($db->query($updateRunningJob));
        $this->assertTrue($db->query($updatePendingJobs));

        $this->bootstrap->execute(sprintf(
            'cd %s && php bin/magento cron:run',
            $this->bootstrap->getSandboxDir()
        ));

        $this->assertTrue($countSuccess < count($db->select($selectSuccessJobs)));

        $db->close();
    }

    /**
     * @return array
     */
    public function cronDataProvider()
    {
        return [
            ['version' => '2.2.0'],
            ['version' => '2.2.2'],
            ['version' => '2.2.*'],
            ['version' => '@stable'],
        ];
    }

    /**
     * @inheritdoc
     */
    protected function tearDown()
    {
        parent::tearDown();
        $this->bootstrap->destroy();
    }
}
