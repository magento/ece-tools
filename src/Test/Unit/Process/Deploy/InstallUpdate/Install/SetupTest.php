<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Unit\Process\Deploy\InstallUpdate\Install;

use Magento\MagentoCloud\Config\Environment;
use Magento\MagentoCloud\Config\Stage\DeployInterface;
use Magento\MagentoCloud\Process\Deploy\InstallUpdate\Install\Setup;
use Magento\MagentoCloud\Shell\ShellInterface;
use Magento\MagentoCloud\Util\UrlManager;
use Magento\MagentoCloud\Util\PasswordGenerator;
use Magento\MagentoCloud\Filesystem\FileList;
use PHPUnit\Framework\TestCase;
use PHPUnit_Framework_MockObject_MockObject as Mock;
use Psr\Log\LoggerInterface;

/**
 * @inheritdoc
 */
class SetupTest extends TestCase
{
    /**
     * @var Setup
     */
    private $process;

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
     * @var FileList|Mock
     */
    private $fileListMock;

    /**
     * @var DeployInterface|Mock
     */
    private $stageConfigMock;

    /**
     * @inheritdoc
     */
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
        $this->passwordGeneratorMock = $this->createMock(PasswordGenerator::class);
        $this->fileListMock = $this->createMock(FileList::class);
        $this->stageConfigMock = $this->getMockForAbstractClass(DeployInterface::class);

        $this->process = new Setup(
            $this->loggerMock,
            $this->urlManagerMock,
            $this->environmentMock,
            $this->shellMock,
            $this->passwordGeneratorMock,
            $this->fileListMock,
            $this->stageConfigMock
        );
    }

    /**
     * @param string $adminEmail
     * @param string $adminName
     * @param string $adminPassword
     * @param string $adminUrl
     * @param string $adminFirstname
     * @param string $adminLastname
     * @param string $adminNameExpected
     * @param string $adminPasswordExpected
     * @param string $adminUrlExpected
     * @param string $adminFirstnameExpected
     * @param string $adminLastnameExpected
     * @dataProvider executeDataProvider
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function testExecute(
        $adminEmail,
        $adminName,
        $adminPassword,
        $adminUrl,
        $adminFirstname,
        $adminLastname,
        $adminNameExpected,
        $adminPasswordExpected,
        $adminUrlExpected,
        $adminFirstnameExpected,
        $adminLastnameExpected
    ) {
        $installUpgradeLog = '/tmp/log.log';

        $this->loggerMock->expects($this->once())
            ->method('info')
            ->with('Installing Magento.');
        $this->urlManagerMock->expects($this->once())
            ->method('getUnSecureUrls')
            ->willReturn(['' => 'http://unsecure.url']);
        $this->urlManagerMock->expects($this->once())
            ->method('getSecureUrls')
            ->willReturn(['' => 'https://secure.url']);
        $this->stageConfigMock->expects($this->exactly(2))
            ->method('get')
            ->willReturn(DeployInterface::VAR_VERBOSE_COMMANDS)
            ->willReturn('-v');
        $this->environmentMock->expects($this->any())
            ->method('getRelationships')
            ->willReturn([
                'database' => [
                    0 => [
                        'host' => 'localhost',
                        'port' => '3306',
                        'path' => 'magento',
                        'username' => 'user',
                        'password' => 'password',
                    ],
                ],
            ]);
        $this->environmentMock->expects($this->any())
            ->method('getVariables')
            ->willReturn([
                'ADMIN_URL' => $adminUrl,
                'ADMIN_LOCALE' => 'fr_FR',
                'ADMIN_FIRSTNAME' => $adminFirstname,
                'ADMIN_LASTNAME' => $adminLastname,
                'ADMIN_EMAIL' => $adminEmail,
                'ADMIN_PASSWORD' => $adminPassword,
                'ADMIN_USERNAME' => $adminName,
            ]);

        $this->passwordGeneratorMock->expects($this->any())
            ->method('generateRandomPassword')
            ->willReturn($adminPasswordExpected);

        $this->fileListMock->expects($this->once())
            ->method('getInstallUpgradeLog')
            ->willReturn($installUpgradeLog);

        $adminCredential = $adminEmail
            ? ' --admin-user=\'' . $adminNameExpected . '\''
                . ' --admin-firstname=\'' . $adminFirstnameExpected . '\' --admin-lastname=\'' . $adminLastnameExpected
                . '\' --admin-email=\'' . $adminEmail . '\' --admin-password=\'' . $adminPasswordExpected . '\''
            : '';
        $this->shellMock->expects($this->once())
            ->method('execute')
            ->with(
                '/bin/bash -c "set -o pipefail;'
                . ' php ./bin/magento setup:install -n --session-save=db --cleanup-database --currency=\'USD\''
                . ' --base-url=\'http://unsecure.url\' --base-url-secure=\'https://secure.url\' --language=\'fr_FR\''
                . ' --timezone=America/Los_Angeles --db-host=\'localhost\' --db-name=\'magento\' --db-user=\'user\''
                . ' --backend-frontname=\'' . $adminUrlExpected . '\''
                . $adminCredential
                . ' --use-secure-admin=1 --ansi --no-interaction'
                . ' --db-password=\'password\' -v'
                . ' | tee -a ' . $installUpgradeLog . '"'
            );

        $this->process->execute();
    }

    /**
     * @return array
     */
    public function executeDataProvider()
    {
        return [
            [
                'adminEmail' => 'admin@example.com',
                'adminName' => 'root',
                'adminPassword' => 'myPassword',
                'adminUrl' => 'admino4ka',
                'adminFirstname' => 'Firstname',
                'adminLastname' => 'Lastname',
                'adminNameExpected' => 'root',
                'adminPasswordExpected' => 'myPassword',
                'adminUrlExpected' => 'admino4ka',
                'adminFirstnameExpected' => 'Firstname',
                'adminLastnameExpected' => 'Lastname',
            ],
            [
                'adminEmail' => 'admin@example.com',
                'adminName' => '',
                'adminPassword' => '',
                'adminUrl' => '',
                'adminFirstname' => '',
                'adminLastname' => '',
                'adminNameExpected' => Environment::DEFAULT_ADMIN_NAME,
                'adminPasswordExpected' => 'generetedPassword',
                'adminUrlExpected' => Environment::DEFAULT_ADMIN_URL,
                'adminFirstnameExpected' => Environment::DEFAULT_ADMIN_FIRSTNAME,
                'adminLastnameExpected' => Environment::DEFAULT_ADMIN_LASTNAME,
            ],
            [
                'adminEmail' => '',
                'adminName' => 'root',
                'adminPassword' => 'myPassword',
                'adminUrl' => 'admino4ka',
                'adminFirstname' => 'Firstname',
                'adminLastname' => 'Lastname',
                'adminNameExpected' => 'root',
                'adminPasswordExpected' => 'myPassword',
                'adminUrlExpected' => 'admino4ka',
                'adminFirstnameExpected' => 'Firstname',
                'adminLastnameExpected' => 'Lastname',
            ],
            [
                'adminEmail' => '',
                'adminName' => '',
                'adminPassword' => '',
                'adminUrl' => '',
                'adminFirstname' => '',
                'adminLastname' => '',
                'adminNameExpected' => Environment::DEFAULT_ADMIN_NAME,
                'adminPasswordExpected' => 'generetedPassword',
                'adminUrlExpected' => Environment::DEFAULT_ADMIN_URL,
                'adminFirstnameExpected' => Environment::DEFAULT_ADMIN_FIRSTNAME,
                'adminLastnameExpected' => Environment::DEFAULT_ADMIN_LASTNAME,
            ],
        ];
    }
}
