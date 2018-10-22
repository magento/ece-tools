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
 * @group php72
 */
class CronTest extends AbstractTest
{
    /**
     * @var ConnectionInterface $db
     */
    private $db;

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
     * @param null $version
     * @param string $locale
     * @param string $stability
     * @dataProvider cronDataProvider
     */
    public function testCron($version = null, $locale = 'en_US', $stability = 'stable')
    {
        $this->bootstrap->run($version, $stability);
        $this->bootstrap->execute(sprintf(
            'cd %s && composer install -n --no-dev --no-progress',
            $this->bootstrap->getSandboxDir()
        ));

        $application = $this->bootstrap->createApplication([
            'variables' => ['ADMIN_EMAIL' => 'admin@example.com', 'ADMIN_LOCALE' => $locale]
        ]);

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

        $this->db = $application->getContainer()->get(ConnectionInterface::class);
        $this->db->close();

        $this->assertTrue($this->db->query('DELETE FROM cron_schedule;'));
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

        $this->checkCronJobForLocale('cron_test_job_timeformat', 300);
        $this->checkCronJobForLocale('cron_test_job_timeformat_six', 360);

        $countSuccess = count($this->db->select($selectSuccessJobs));
        $this->assertTrue($this->db->query($addRunningJob));
        $this->assertTrue($this->db->query($updatePendingJobs));

        $this->bootstrap->execute(sprintf(
            'cd %s && php bin/magento cron:run',
            $this->bootstrap->getSandboxDir()
        ));

        $this->assertTrue($countSuccess == count($this->db->select($selectSuccessJobs)));

        $this->assertTrue($this->db->query($updateRunningJob));
        $this->assertTrue($this->db->query($updatePendingJobs));

        $this->bootstrap->execute(sprintf(
            'cd %s && php bin/magento cron:run',
            $this->bootstrap->getSandboxDir()
        ));

        $this->assertTrue($countSuccess < count($this->db->select($selectSuccessJobs)));

        $this->db->close();
    }

    /**
     * @return array
     */
    public function cronDataProvider(): array
    {
        return [
            ['version' => '~2.3.0', 'locale' => 'fr_FR', 'stability' => 'beta'],
        ];
    }

    /**
     * @param string $jobCode
     * @param int $timeInterval
     * @return void
     */
    private function checkCronJobForLocale(string $jobCode, int $timeInterval)
    {
        $jobs = $this->db->select('SELECT * FROM cron_schedule WHERE job_code = "' . $jobCode . '"');
        $currentTime = time();
        $this->assertTrue(count($jobs) > 1);

        reset($jobs);
        $currentJob = current($jobs);
        while ($nextJob = next($jobs)) {
            $currentJobTime = strtotime($currentJob['scheduled_at']);
            $nextJobTime = strtotime($nextJob['scheduled_at']);
            $currentJob = $nextJob;

            $this->assertTrue($currentJobTime - $currentTime <= 86400);
            $this->assertSame($timeInterval, $nextJobTime - $currentJobTime, $nextJobTime . ' - ' . $currentJobTime);
        }
    }

    /**
     * @inheritdoc
     */
    protected function tearDown()
    {
        Bootstrap::getInstance()->execute(sprintf(
            'cd %s && php bin/magento setup:uninstall -n',
            Bootstrap::getInstance()->getSandboxDir()
        ));
        Bootstrap::getInstance()->createApplication([])->getContainer()
            ->get(ConnectionInterface::class)
            ->close();
        Bootstrap::getInstance()->execute(sprintf(
            'cd %s && rm -rf vendor/*',
            Bootstrap::getInstance()->getSandboxDir()
        ));
        Bootstrap::getInstance()->destroy();
    }
}
