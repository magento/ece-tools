<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\DockerIntegration;

/**
 * @inheritdoc
 *
 * @php 7.2
 */
class AdminCredentialTest extends AbstractTest
{
    /**
     * @var string
     */
    protected $magentoCloudTemplate = 'master';

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
        (new Process\GitClone($this->magentoCloudTemplate))
            ->setTimeout(null)
            ->mustRun();
        (new Process\ComposerInstall())
            ->setTimeout(null)
            ->mustRun();

        $code = (new Process\Ece('build', Config::DEFAULT_CONTAINER))
            ->setTimeout(null)
            ->run();

        $this->assertSame(0, $code);

        $code = (new Process\Ece('deploy', Config::CONTAINER_DEPLOY, $variables))
            ->setTimeout(null)
            ->run();

        $this->assertSame(0, $code);

        $code = (new Process\Ece('post-deploy', Config::CONTAINER_DEPLOY))
            ->setTimeout(null)
            ->run();

        $this->assertSame(0, $code);

        $log = $this->getCloudLog();
        $this->assertContains($installMessage, $log);
        $this->assertNotContains('--admin-user', $log);
        $this->assertNotContains('--admin-firstname', $log);
        $this->assertNotContains('--admin-lastname', $log);
        $this->assertNotContains('--admin-email', $log);
        $this->assertNotContains('--admin-password', $log);

        // Upgrade
        $code = (new Process\Ece('deploy', Config::CONTAINER_DEPLOY, $variables))
            ->setTimeout(null)
            ->run();

        $this->assertSame(0, $code);

        $this->assertContains($upgradeMessage, $this->getCloudLog());
    }

    /**
     * @return array
     */
    public function installWithoutAdminEmailDataProvider()
    {
        return [
            [
                'variables' => ['MAGENTO_CLOUD_VARIABLES' => []],
                'installMessage' => '',
                'upgradeMessage' => '',
            ],
            [
                'variables' => ['MAGENTO_CLOUD_VARIABLES' => ['ADMIN_USERNAME' => 'MyLogin']],
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
        (new Process\GitClone($this->magentoCloudTemplate))
            ->setTimeout(null)
            ->mustRun();
        (new Process\ComposerInstall())
            ->setTimeout(null)
            ->mustRun();

        $code = (new Process\Ece('build', Config::DEFAULT_CONTAINER))
            ->setTimeout(null)
            ->run();

        $this->assertSame(0, $code);

        $code = (new Process\Ece('deploy', Config::CONTAINER_DEPLOY, $variables))
            ->setTimeout(null)
            ->run();

        $this->assertSame(0, $code);

        $code = (new Process\Ece('post-deploy', Config::CONTAINER_DEPLOY))
            ->setTimeout(null)
            ->run();

        $this->assertSame(0, $code);

        $tmpFile = tempnam(sys_get_temp_dir(), 'credentials_email');
        (new Process\Copy('/var/credentials_email.txt', $tmpFile))
            ->setTimeout(null)
            ->run();

        $credentialsEmail = file_get_contents($tmpFile);
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

        // Upgrade
        $code = (new Process\Ece('deploy', Config::CONTAINER_DEPLOY, $variables))
            ->setTimeout(null)
            ->run();

        $this->assertSame(0, $code);

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
                    'MAGENTO_CLOUD_VARIABLES' => ['ADMIN_EMAIL' => 'admin@example.com'],
                ],
                'expectedAdminEmail' => 'admin@example.com',
                'expectedAdminUsername' => 'admin',
                'expectedAdminUrl' => 'admin',
            ],
            [
                'variables' => [
                    'MAGENTO_CLOUD_VARIABLES' => [
                        'ADMIN_EMAIL' => 'admin2@example.com',
                        'ADMIN_URL' => 'root',
                        'ADMIN_USERNAME' => 'myusername',
                    ],
                ],
                'expectedAdminEmail' => 'admin2@example.com',
                'expectedAdminUsername' => 'root',
                'expectedAdminUrl' => 'myusername',
            ]
        ];
    }

    /**
     * @return string
     */
    private function getCloudLog(): string
    {
        $tmpFile = tempnam(sys_get_temp_dir(), '/cloud.log');
        (new Process\Copy('/var/log/cloud.log', $tmpFile))
            ->setTimeout(null)
            ->run();
        return file_get_contents($tmpFile);
    }
}
