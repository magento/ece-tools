<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Unit\Process\Deploy\InstallUpdate\Install;

use Magento\MagentoCloud\Config\Environment;
use Magento\MagentoCloud\Config\Stage\DeployInterface;
use Magento\MagentoCloud\Process\Deploy\InstallUpdate\Install\Setup;
use Magento\MagentoCloud\Shell\ExecBinMagento;
use Magento\MagentoCloud\Shell\ShellException;
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
     * @var ExecBinMagento|Mock
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
     * @var string
     */
    private $logPath = ECE_BP . '/tests/unit/tmp/install.log';

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
        $this->shellMock = $this->createMock(ExecBinMagento::class);
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

        // Initialize log file
        if (!is_dir(dirname($this->logPath))) {
            mkdir(dirname($this->logPath), 0777, true);
        }
        file_put_contents($this->logPath, 'Previous log' . PHP_EOL);
    }

    /**
     * @param $adminName
     * @param $adminPassword
     * @param $adminUrl
     * @param $adminFirstname
     * @param $adminLastname
     * @param $adminNameExpected
     * @param $adminPasswordExpected
     * @param $adminUrlExpected
     * @param $adminFirstnameExpected
     * @param $adminLastnameExpected
     * @dataProvider executeDataProvider
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function testExecute(
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
        $argumentMatcher = function (array $subject) use (
            $adminUrlExpected,
            $adminNameExpected,
            $adminFirstnameExpected,
            $adminLastnameExpected,
            $adminPasswordExpected
        ) {
            $this->assertContains('--session-save=db', $subject);
            $this->assertContains('--cleanup-database', $subject);
            $this->assertContains('--currency=USD', $subject);
            $this->assertContains('--base-url=http://unsecure.url', $subject);
            $this->assertContains('--base-url-secure=https://secure.url', $subject);
            $this->assertContains('--language=fr_FR', $subject);
            $this->assertContains('--backend-frontname=' . $adminUrlExpected, $subject);
            $this->assertContains('--admin-user=' . $adminNameExpected, $subject);
            $this->assertContains('--admin-firstname=' . $adminFirstnameExpected, $subject);
            $this->assertContains('--admin-lastname=' . $adminLastnameExpected, $subject);
            $this->assertContains('--admin-email=admin@example.com', $subject);
            $this->assertContains('--admin-password=' . $adminPasswordExpected, $subject);
            $this->assertContains('--use-secure-admin=1', $subject);
            $this->assertContains('--db-password=password', $subject);
            $this->assertContains('-v', $subject);

            return true;
        };

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
                'ADMIN_EMAIL' => 'admin@example.com',
                'ADMIN_PASSWORD' => $adminPassword,
                'ADMIN_USERNAME' => $adminName,
            ]);

        $this->passwordGeneratorMock->expects($this->any())
            ->method('generateRandomPassword')
            ->willReturn($adminPasswordExpected);

        $this->fileListMock->expects($this->once())
            ->method('getInstallUpgradeLog')
            ->willReturn($this->logPath);

        $this->shellMock->expects($this->once())
            ->method('execute')
            ->with('setup:install', $this->callback($argumentMatcher))
            ->willReturn(['Doing install', 'Install complete']);

        $this->process->execute();

        $this->assertFileIsReadable($this->logPath);
        $this->assertSame("Previous log\nDoing install\nInstall complete\n", file_get_contents($this->logPath));
    }

    /**
     * @return array
     */
    public function executeDataProvider()
    {
        return [
            [
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

    /**
     * @expectedException Magento\MagentoCloud\Shell\ShellException
     * @expectedExceptionMessage Error during command execution
     */
    public function testExecuteWithException()
    {
        $this->fileListMock->expects($this->once())
            ->method('getInstallUpgradeLog')
            ->willReturn($this->logPath);
        $this->shellMock->method('execute')
            ->willThrowException(new ShellException('Error during command execution', 1, ['Output from command']));

        $this->process->execute();

        $this->assertFileIsReadable($this->logPath);
        $this->assertSame("Previous log\nOutput from command\n", file_get_contents($this->logPath));
    }

    /**
     * @expectedException Exception
     * @expectedExceptionMessage Something else has gone wrong
     */
    public function testExecuteWithExceptionOther()
    {
        $this->fileListMock->expects($this->once())
            ->method('getInstallUpgradeLog')
            ->willReturn($this->logPath);
        $this->shellMock->method('execute')
            ->willThrowException(new \Exception('Something else has gone wrong'));

        $this->process->execute();

        $this->assertFileIsReadable($this->logPath);
        $this->assertSame("Previous log\nSomething else has gone wrong\n", file_get_contents($this->logPath));
    }
}
