<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Unit\Process\Deploy\InstallUpdate\Install;

use Magento\MagentoCloud\Config\Environment;
use Magento\MagentoCloud\Process\Deploy\InstallUpdate\Install\Setup;
use Magento\MagentoCloud\Shell\ShellInterface;
use Magento\MagentoCloud\Util\PasswordGenerator;
use Magento\MagentoCloud\Util\UrlManager;
use PHPUnit\Framework\TestCase;
use PHPUnit_Framework_MockObject_MockObject as Mock;
use Psr\Log\LoggerInterface;

class SetupTest extends TestCase
{
    /**
     * @var ShellInterface|Mock
     */
    private $shellMock;

    /**
     * @var LoggerInterface|Mock
     */
    private $loggerMock;

    /**
     * @var Environment|Mock
     */
    private $environmentMock;

    /**
     * @var UrlManager|Mock
     */
    private $urlManagerMock;

    /**
     * @var PasswordGenerator|Mock
     */
    private $passwordGeneratorMock;

    /**
     * @var Setup
     */
    private $process;

    protected function setUp()
    {
        $this->environmentMock = $this->getMockBuilder(Environment::class)
            ->setMethods(['getVerbosityLevel', 'getVariables', 'getRelationships'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->urlManagerMock = $this->createMock(UrlManager::class);
        $this->shellMock = $this->getMockBuilder(ShellInterface::class)
            ->getMockForAbstractClass();
        $this->loggerMock = $this->getMockBuilder(LoggerInterface::class)
            ->getMockForAbstractClass();
        $this->passwordGeneratorMock = $this->getMockBuilder(PasswordGenerator::class)
            ->setMethods(['generateRandomString'])
            ->getMockForAbstractClass();

        $this->process = new Setup(
            $this->loggerMock,
            $this->urlManagerMock,
            $this->environmentMock,
            $this->shellMock,
            $this->passwordGeneratorMock
        );
    }

    public function testExecute()
    {

        //ARRANGE
        $defaultCurrency = "USD";
        $urlUnsecure = "http://unsecure.url";
        $urlSecure = "https://secure.url";
        $adminLocale = "fr_FR";
        $timeZone = "America/Los_Angeles";
        $dbHost = "localhost";
        $dbName = "magento";
        $dbUser = "user";
        $adminUrl = "admin_url";
        $adminUsername = "admin";
        $adminFirstname = "Firstname";
        $adminLastname = "Lastname";
        $adminEmail = "john@example.com";
        $adminPassword = "QoAav7ESLd1xvB5oT8hj";
        $randomlyGeneratedPassword = "vB5oT8hjQoAav7ESLd1x";
        $dbPassword = "password";
        $verbosityLevel = " -v";
        $this->loggerMock->expects($this->once())
            ->method('info')
            ->with('Installing Magento.');
        $this->urlManagerMock->expects($this->once())
            ->method('getUnSecureUrls')
            ->willReturn(['' => 'http://unsecure.url']);
        $this->urlManagerMock->expects($this->once())
            ->method('getSecureUrls')
            ->willReturn(['' => 'https://secure.url']);
        $this->environmentMock->expects($this->once())
            ->method('getVerbosityLevel')
            ->willReturn(' -v');
        $this->environmentMock->expects($this->any())
            ->method('getRelationships')
            ->willReturn([
                'database' => [
                    0 => [
                        'host' => 'localhost',
                        'port' => '3306',
                        'path' => 'magento',
                        'username' => 'user',
                        'password' => 'password'
                    ]
                ],
            ]);
        $this->environmentMock->expects($this->any())
            ->method('getVariables')
            ->willReturn([
                'ADMIN_URL' => $adminUrl,
                'ADMIN_LOCALE' => $adminLocale,
                'ADMIN_FIRSTNAME' => $adminFirstname,
                'ADMIN_LASTNAME' => $adminLastname,
                'ADMIN_USERNAME' => $adminUsername,
                'ADMIN_PASSWORD' => $adminPassword,
                'ADMIN_EMAIL' => $adminEmail,
            ]);
        $this->passwordGeneratorMock->expects($this->any())
            ->method('generateRandomString')
            ->willReturn($randomlyGeneratedPassword);

        //ASSERT
        $command =
            "php ./bin/magento setup:install"
            . " " . "--session-save=db"
            . " " . "--cleanup-database"
            . " " . escapeshellarg("--currency={$defaultCurrency}")
            . " " . escapeshellarg("--base-url={$urlUnsecure}")
            . " " . escapeshellarg("--base-url-secure={$urlSecure}")
            . " " . escapeshellarg("--language={$adminLocale}")
            . " " . escapeshellarg("--timezone={$timeZone}")
            . " " . escapeshellarg("--db-host={$dbHost}")
            . " " . escapeshellarg("--db-name={$dbName}")
            . " " . escapeshellarg("--db-user={$dbUser}")
            . " " . escapeshellarg("--backend-frontname={$adminUrl}")
            . " " . escapeshellarg("--admin-user={$adminUsername}")
            . " " . escapeshellarg("--admin-firstname={$adminFirstname}")
            . " " . escapeshellarg("--admin-lastname={$adminLastname}")
            . " " . escapeshellarg("--admin-email={$adminEmail}")
            . " " . escapeshellarg("--admin-password={$randomlyGeneratedPassword}"); // Note: This password gets changed later in updateAdminCredentials
        if (strlen($dbPassword)) {
            $command .= " " . escapeshellarg("--db-password={$dbPassword}");
        }
        $command .= $verbosityLevel;
        $this->shellMock->expects($this->once())
            ->method('execute')
            ->with($command);

        //ACT
        $this->process->execute();
    }
}
