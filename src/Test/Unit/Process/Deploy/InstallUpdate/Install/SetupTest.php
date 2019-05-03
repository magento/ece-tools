<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Test\Unit\Process\Deploy\InstallUpdate\Install;

use Magento\MagentoCloud\Config\Environment;
use Magento\MagentoCloud\Config\Stage\DeployInterface;
use Magento\MagentoCloud\DB\Data\ConnectionFactory;
use Magento\MagentoCloud\DB\Data\ConnectionInterface;
use Magento\MagentoCloud\Config\SearchEngine\ElasticSuite;
use Magento\MagentoCloud\Process\Deploy\InstallUpdate\Install\Setup;
use Magento\MagentoCloud\Process\ProcessException;
use Magento\MagentoCloud\Shell\ShellInterface;
use Magento\MagentoCloud\Util\UrlManager;
use Magento\MagentoCloud\Util\PasswordGenerator;
use Magento\MagentoCloud\Filesystem\FileList;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
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
     * @var ShellInterface|MockObject
     */
    private $shellMock;

    /**
     * @var LoggerInterface|MockObject
     */
    private $loggerMock;

    /**
     * @var Environment|MockObject
     */
    private $environmentMock;

    /**
     * @var UrlManager|MockObject
     */
    private $urlManagerMock;

    /**
     * @var PasswordGenerator|MockObject
     */
    private $passwordGeneratorMock;

    /**
     * @var FileList|MockObject
     */
    private $fileListMock;

    /**
     * @var DeployInterface|MockObject
     */
    private $stageConfigMock;

    /**
     * @var ConnectionInterface|MockObject
     */
    private $connectionDataMock;

    /**
     * @var ElasticSuite|MockObject
     */
    private $elasticSuiteMock;

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
        $this->connectionDataMock = $this->getMockForAbstractClass(ConnectionInterface::class);
        /** @var ConnectionFactory|MockObject $connectionFactoryMock */
        $connectionFactoryMock = $this->createMock(ConnectionFactory::class);
        $connectionFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($this->connectionDataMock);
        $this->elasticSuiteMock = $this->createMock(ElasticSuite::class);

        $this->process = new Setup(
            $this->loggerMock,
            $this->urlManagerMock,
            $this->environmentMock,
            $connectionFactoryMock,
            $this->shellMock,
            $this->passwordGeneratorMock,
            $this->fileListMock,
            $this->stageConfigMock,
            $this->elasticSuiteMock
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
     * @param bool $elasticSuite
     * @dataProvider executeDataProvider
     * @throws ProcessException
     *
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
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
        $adminLastnameExpected,
        bool $elasticSuite = false
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
        $this->connectionDataMock->expects($this->once())
            ->method('getPassword')
            ->willReturn('password');
        $this->connectionDataMock->expects($this->once())
            ->method('getHost')
            ->willReturn('localhost');
        $this->connectionDataMock->expects($this->once())
            ->method('getDbName')
            ->willReturn('magento');
        $this->connectionDataMock->expects($this->once())
            ->method('getUser')
            ->willReturn('user');
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

        $elasticSuiteOption = '';

        if ($elasticSuite) {
            $this->elasticSuiteMock->expects($this->once())
                ->method('isAvailable')
                ->willReturn(true);
            $this->elasticSuiteMock->expects($this->once())
                ->method('get')
                ->willReturn([
                    'es_client' => [
                        'servers' => 'localhost:9200'
                    ]
                ]);
            $elasticSuiteOption = ' --es-hosts=\'localhost:9200\'';
        }

        $adminCredential = $adminEmail
            ? ' --admin-user=\'' . $adminNameExpected . '\''
            . ' --admin-firstname=\'' . $adminFirstnameExpected . '\' --admin-lastname=\'' . $adminLastnameExpected
            . '\' --admin-email=\'' . $adminEmail . '\' --admin-password=\'' . $adminPasswordExpected . '\''
            : '';
        $this->shellMock->expects($this->exactly(2))
            ->method('execute')
            ->withConsecutive(
                ['echo \'Installation time: \'$(date) | tee -a ' . $installUpgradeLog],
                [
                    '/bin/bash -c "set -o pipefail;'
                    . ' php ./bin/magento setup:install -n --session-save=db --cleanup-database --currency=\'USD\''
                    . ' --base-url=\'http://unsecure.url\' --base-url-secure=\'https://secure.url\''
                    . ' --language=\'fr_FR\''
                    . ' --timezone=America/Los_Angeles --db-host=\'localhost\' --db-name=\'magento\' --db-user=\'user\''
                    . ' --backend-frontname=\'' . $adminUrlExpected . '\''
                    . $adminCredential
                    . ' --use-secure-admin=1 --use-rewrites=1 --ansi --no-interaction'
                    . ' --db-password=\'password\' -v'
                    . $elasticSuiteOption
                    . ' | tee -a ' . $installUpgradeLog . '"'
                ]
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
                true
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
