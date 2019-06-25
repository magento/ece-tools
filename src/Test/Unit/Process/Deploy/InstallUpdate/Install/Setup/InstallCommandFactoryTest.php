<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Test\Unit\Process\Deploy\InstallUpdate\Install\Setup;

use Magento\MagentoCloud\Config\Database\MergedConfig;
use Magento\MagentoCloud\Config\Environment;
use Magento\MagentoCloud\Config\SearchEngine\ElasticSuite;
use Magento\MagentoCloud\Config\Stage\DeployInterface;
use Magento\MagentoCloud\DB\Data\ConnectionFactory;
use Magento\MagentoCloud\DB\Data\ConnectionInterface;
use Magento\MagentoCloud\Process\Deploy\InstallUpdate\Install\Setup\InstallCommandFactory;
use Magento\MagentoCloud\Util\PasswordGenerator;
use Magento\MagentoCloud\Util\UrlManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @inheritdoc
 */
class InstallCommandFactoryTest extends TestCase
{
    /**
     * @var InstallCommandFactory
     */
    private $installCommandFactory;

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
     * @var MergedConfig|MockObject
     */
    private $mergedConfigMock;

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
        $this->passwordGeneratorMock = $this->createMock(PasswordGenerator::class);
        $this->stageConfigMock = $this->getMockForAbstractClass(DeployInterface::class);
        $this->connectionDataMock = $this->getMockForAbstractClass(ConnectionInterface::class);
        /** @var ConnectionFactory|MockObject $connectionFactoryMock */
        $connectionFactoryMock = $this->createMock(ConnectionFactory::class);
        $connectionFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($this->connectionDataMock);
        $this->elasticSuiteMock = $this->createMock(ElasticSuite::class);
        $this->mergedConfigMock = $this->createMock(MergedConfig::class);

        $this->installCommandFactory = new InstallCommandFactory(
            $this->urlManagerMock,
            $this->environmentMock,
            $connectionFactoryMock,
            $this->passwordGeneratorMock,
            $this->stageConfigMock,
            $this->elasticSuiteMock,
            $this->mergedConfigMock
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
     * @param array $mergedConfig
     * @dataProvider executeDataProvider
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
        bool $elasticSuite = false,
        array $mergedConfig = []
    ) {
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
        $this->mergedConfigMock->expects($this->once())
            ->method('get')
            ->willReturn($mergedConfig);

        $this->passwordGeneratorMock->expects($this->any())
            ->method('generateRandomPassword')
            ->willReturn($adminPasswordExpected);

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

        $dbPrefix = isset($mergedConfig['table_prefix']) ? " --db-prefix='" . $mergedConfig['table_prefix'] . "'" : '';

        $expectedCommand =
            'php ./bin/magento setup:install -n --session-save=db --cleanup-database'
            . ' --use-secure-admin=1 --use-rewrites=1 --ansi --no-interaction --currency=\'USD\''
            . ' --base-url=\'http://unsecure.url\' --base-url-secure=\'https://secure.url\''
            . ' --backend-frontname=\'' . $adminUrlExpected . '\''
            . ' --language=\'fr_FR\''
            . ' --timezone=America/Los_Angeles --db-host=\'localhost\' --db-name=\'magento\' --db-user=\'user\''
            . ' --db-password=\'password\''
            . $dbPrefix
            . $adminCredential
            . $elasticSuiteOption
            . ' -v';

        $this->assertEquals(
            $expectedCommand,
            $this->installCommandFactory->create()
        );
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
                true,
                [],
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
                false,
                ['table_prefix' => 'prefix'],
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
                false,
                [],
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
                false,
                [],
            ],
        ];
    }
}
