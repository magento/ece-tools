<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Integration;

use PHPUnit\Framework\TestCase;
use Magento\MagentoCloud\Command\Build;
use Magento\MagentoCloud\Command\Deploy;
use Magento\MagentoCloud\Config\Environment;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * @inheritdoc
 */
class AdminCredentialTest extends TestCase
{
    /**
     * @var Bootstrap
     */
    private $bootstrap;

    /**
     * @var array
     */
    protected $env = [];

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        $this->bootstrap = Bootstrap::create();

        $this->bootstrap->execute(sprintf(
            'cd %s && php bin/magento module:enable --all',
            $this->bootstrap->getSandboxDir()
        ));

        $this->env = $_ENV;
    }

    /**
     * @inheritdoc
     */
    protected function tearDown()
    {
        shell_exec(sprintf(
            'cd %s && php bin/magento setup:uninstall -n',
            $this->bootstrap->getSandboxDir()
        ));

        $_ENV = $this->env;
    }

    /**
     * @expectedException \Exception
     * @codingStandardsIgnoreStart
     * @expectedExceptionMessage ADMIN_EMAIL not set during install!  We need this variable set to send the password reset email. Please set ADMIN_EMAIL and retry deploy.
     * @codingStandardsIgnoreEnd
     */
    public function testInstallWithoutAdminEmail()
    {
        $application = $this->bootstrap->createApplication(['variables' => []]);

        $commandTester = new CommandTester(
            $application->get(Build::NAME)
        );
        $commandTester->execute([]);

        $this->assertSame(0, $commandTester->getStatusCode());

        $commandTester = new CommandTester(
            $application->get(Deploy::NAME)
        );
        $commandTester->execute([]);

        $this->assertSame(1, $commandTester->getStatusCode());
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

        $commandTester = new CommandTester(
            $application->get(Build::NAME)
        );
        $commandTester->execute([]);

        $this->assertSame(0, $commandTester->getStatusCode());

        $commandTester = new CommandTester(
            $application->get(Deploy::NAME)
        );
        $commandTester->execute([]);

        $this->assertSame(0, $commandTester->getStatusCode());

        $credentialsEmail = file_get_contents($this->bootstrap->getSandboxDir() . '/var/credentials_email.txt');
        $this->assertContains($expectedAdminEmail, $credentialsEmail);
        $this->assertContains($expectedAdminUsername, $credentialsEmail);
        $this->assertContains($expectedAdminUrl, $credentialsEmail);
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

    public function testCheckDuplicates()
    {
        $application = $this->bootstrap->createApplication([]);

        $commandTester = new CommandTester(
            $application->get(Build::NAME)
        );
        $commandTester->execute([]);
        $this->assertSame(0, $commandTester->getStatusCode());

        $application = $this->bootstrap->createApplication([
            'variables' => [
                'ADMIN_EMAIL' => 'admin@example.com',
                'ADMIN_USERNAME' => 'admin'
            ]
        ]);
        // Install Magento
        $commandTester = new CommandTester(
            $application->get(Deploy::NAME)
        );
        $commandTester->execute([]);
        $this->assertSame(0, $commandTester->getStatusCode());

        // Upgrade Magento with the same environment variables
        $commandTester = new CommandTester(
            $application->get(Deploy::NAME)
        );
        $commandTester->execute([]);
        $this->assertSame(0, $commandTester->getStatusCode());
        $this->assertContains('Updating admin credentials: nothing to update.', $this->getCloudLog());
        $this->assertContains('Some administrator already uses this email admin@example.com', $this->getCloudLog());
        $this->assertContains('Some administrator already uses this username admin', $this->getCloudLog());
    }

    private function getCloudLog()
    {
        return file_get_contents(
            $this->bootstrap->getSandboxDir() . '/' . \Magento\MagentoCloud\App\Logger::DEPLOY_LOG_PATH
        );
    }
}
