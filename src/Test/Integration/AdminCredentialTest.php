<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Integration;

use Magento\MagentoCloud\Application;
use Magento\MagentoCloud\Command\Build;
use Magento\MagentoCloud\Command\Deploy;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * {@inheritdoc}
 *
 * @group php71
 */
class AdminCredentialTest extends AbstractTest
{
    /**
     * @var array
     */
    protected $env = [];

    /**
     * {@inheritdoc}
     *
     * @throws \Exception
     */
    public static function setUpBeforeClass()
    {
        Bootstrap::getInstance()->run('2.2.*');
    }

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        parent::setUp();

        $this->env = $_ENV;
    }

    /**
     * @inheritdoc
     */
    protected function tearDown()
    {
        parent::tearDown();

        $_ENV = $this->env;
    }

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
     * @param array $variables
     * @param string $installMessage
     * @param string $upgradeMessage
     * @return void
     * @dataProvider installWithoutAdminEmailDataProvider
     */
    public function testInstallWithoutAdminEmail(
        array $variables,
        string $installMessage,
        string $upgradeMessage
    ) {
        $application = $this->bootstrap->createApplication(['variables' => $variables]);

        $this->executeAndAssert(Build::NAME, $application);

        // Install Magento
        $this->executeAndAssert(Deploy::NAME, $application);

        $log = $this->getCloudLog();
        $this->assertContains($installMessage, $log);
        $this->assertNotContains('--admin-user', $log);
        $this->assertNotContains('--admin-firstname', $log);
        $this->assertNotContains('--admin-lastname', $log);
        $this->assertNotContains('--admin-email', $log);
        $this->assertNotContains('--admin-password', $log);

        // Upgrade Magento
        $this->executeAndAssert(Deploy::NAME, $application);

        $this->assertContains($upgradeMessage, $this->getCloudLog());
    }

    /**
     * @return array
     */
    public function installWithoutAdminEmailDataProvider()
    {
        return [
            [
                'variables' => [],
                'installMessage' => '',
                'upgradeMessage' => '',
            ],
            [
                'variables' => ['ADMIN_USERNAME' => 'MyLogin'],
                'installMessage' => 'The following admin data was ignored and an admin was not created because'
                    . ' admin email is not set: admin login',
                'upgradeMessage' => 'The following admin data is required to create an admin user during initial'
                    . ' installation only and is ignored during upgrade process: admin login',
            ],
        ];
    }

    /**
     * @param array $variables
     * @param string $expectedAdminEmail
     * @param string $expectedAdminUsername
     * @param string $expectedAdminUrl
     * @dataProvider installWithDifferentVariablesDataProvider
     */
    public function testInstallWithDifferentVariables(
        $variables,
        $expectedAdminEmail,
        $expectedAdminUsername,
        $expectedAdminUrl
    ) {
        $application = $this->bootstrap->createApplication(['variables' => $variables]);

        $this->executeAndAssert(Build::NAME, $application);
        $this->executeAndAssert(Deploy::NAME, $application);

        $credentialsEmail = file_get_contents($this->bootstrap->getSandboxDir() . '/var/credentials_email.txt');
        $this->assertContains($expectedAdminEmail, $credentialsEmail);
        $this->assertContains($expectedAdminUsername, $credentialsEmail);
        $this->assertContains($expectedAdminUrl, $credentialsEmail);

        $log = $this->getCloudLog();
        $this->assertContains('--admin-user', $log);
        $this->assertContains('--admin-firstname', $log);
        $this->assertContains('--admin-lastname', $log);
        $this->assertContains('--admin-email', $log);
        $this->assertContains('--admin-password', $log);
        $this->assertNotContains(
            'The following admin data was ignored and an admin was not created because admin email is not set',
            $log
        );

        $this->executeAndAssert(Deploy::NAME, $application);
        $this->assertNotContains(
            'The following admin data is required to create an admin user during initial installation only'
                . ' and is ignored during upgrade process: admin login',
            $this->getCloudLog()
        );
    }

    /**
     * @return array
     */
    public function installWithDifferentVariablesDataProvider()
    {
        return [
            [
                'variables' => [
                    'ADMIN_EMAIL' => 'admin@example.com',
                ],
                'expectedAdminEmail' => 'admin@example.com',
                'expectedAdminUsername' => 'admin',
                'expectedAdminUrl' => 'admin',
            ],
            [
                'variables' => [
                    'ADMIN_EMAIL' => 'admin2@example.com',
                    'ADMIN_URL' => 'root',
                    'ADMIN_USERNAME' => 'myusername',
                ],
                'expectedAdminEmail' => 'admin2@example.com',
                'expectedAdminUsername' => 'root',
                'expectedAdminUrl' => 'myusername',
            ]
        ];
    }

    private function getCloudLog()
    {
        return file_get_contents($this->bootstrap->getSandboxDir() . '/var/log/cloud.log');
    }
}
