<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Test\Unit\Step\Deploy\InstallUpdate\Install\Setup;

use Magento\MagentoCloud\Config\AdminDataInterface;
use Magento\MagentoCloud\Config\Amqp as AmqpConfig;
use Magento\MagentoCloud\Config\ConfigException;
use Magento\MagentoCloud\Config\Database\DbConfig;
use Magento\MagentoCloud\Config\RemoteStorage;
use Magento\MagentoCloud\Config\SearchEngine\ElasticSuite;
use Magento\MagentoCloud\Config\Stage\DeployInterface;
use Magento\MagentoCloud\DB\Data\ConnectionFactory;
use Magento\MagentoCloud\DB\Data\ConnectionInterface;
use Magento\MagentoCloud\Package\MagentoVersion;
use Magento\MagentoCloud\Service\ActiveMq;
use Magento\MagentoCloud\Service\ElasticSearch;
use Magento\MagentoCloud\Service\OpenSearch;
use Magento\MagentoCloud\Step\Deploy\InstallUpdate\Install\Setup\InstallCommandFactory;
use Magento\MagentoCloud\Util\PasswordGenerator;
use Magento\MagentoCloud\Util\UrlManager;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @inheritdoc
 * @see InstallCommandFactory
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
#[AllowMockObjectsWithoutExpectations]
class InstallCommandFactoryTest extends TestCase
{
    /**
     * @var InstallCommandFactory
     */
    private $installCommandFactory;

    /**
     * @var AdminDataInterface|MockObject
     */
    private $adminDataMock;

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
     * @var DbConfig|MockObject
     */
    private $dbConfigMock;

    /**
     * @var MagentoVersion|MockObject
     */
    private $magentoVersionMock;

    /**
     * @var ElasticSearch|MockObject
     */
    private $elasticSearchMock;

    /**
     * @var OpenSearch|MockObject
     */
    private $openSearchMock;

    /**
     * @var RemoteStorage|MockObject
     */
    private $remoteStorageMock;

    /**
     * @var AmqpConfig|MockObject
     */
    private $amqpConfigMock;

    /**
     * @var ActiveMq|MockObject
     */
    private $activeMqMock;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->adminDataMock = $this->createMock(AdminDataInterface::class);
        $this->urlManagerMock = $this->createMock(UrlManager::class);
        $this->passwordGeneratorMock = $this->createMock(PasswordGenerator::class);
        $this->stageConfigMock = $this->createMock(DeployInterface::class);
        $this->connectionDataMock = $this->createMock(ConnectionInterface::class);
        /** @var ConnectionFactory|MockObject $connectionFactoryMock */
        $connectionFactoryMock = $this->createMock(ConnectionFactory::class);
        $connectionFactoryMock->expects(self::once())
            ->method('create')
            ->willReturn($this->connectionDataMock);
        $this->elasticSuiteMock = $this->createMock(ElasticSuite::class);
        $this->dbConfigMock = $this->createMock(DbConfig::class);
        $this->magentoVersionMock = $this->createMock(MagentoVersion::class);
        $this->elasticSearchMock = $this->createMock(ElasticSearch::class);
        $this->openSearchMock = $this->createMock(OpenSearch::class);
        $this->remoteStorageMock = $this->createMock(RemoteStorage::class);
        $this->amqpConfigMock = $this->createMock(AmqpConfig::class);
        $this->activeMqMock = $this->createMock(ActiveMq::class);

        $this->installCommandFactory = new InstallCommandFactory(
            $this->urlManagerMock,
            $this->adminDataMock,
            $connectionFactoryMock,
            $this->passwordGeneratorMock,
            $this->stageConfigMock,
            $this->elasticSuiteMock,
            $this->dbConfigMock,
            $this->magentoVersionMock,
            $this->elasticSearchMock,
            $this->openSearchMock,
            $this->remoteStorageMock,
            $this->amqpConfigMock,
            $this->activeMqMock
        );
    }

    /**
     * Test execute method.
     *
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
     * @return void
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    #[DataProvider('executeDataProvider')]
    public function testExecute(
        string $adminEmail,
        string $adminName,
        string $adminPassword,
        string $adminUrl,
        string $adminFirstname,
        string $adminLastname,
        string $adminNameExpected,
        string $adminPasswordExpected,
        string $adminUrlExpected,
        string $adminFirstnameExpected,
        string $adminLastnameExpected,
        bool $elasticSuite = false,
        array $mergedConfig = []
    ): void {
        $this->mockBaseConfig(
            $adminEmail,
            $adminName,
            $adminPassword,
            $adminUrl,
            $adminFirstname,
            $adminLastname
        );

        $this->dbConfigMock->expects(self::once())
            ->method('get')
            ->willReturn($mergedConfig);
        $this->passwordGeneratorMock->method('generateRandomPassword')
            ->willReturn($adminPasswordExpected);

        $elasticSuiteOption = '';

        if ($elasticSuite) {
            $this->elasticSuiteMock->expects(self::once())
                ->method('isAvailable')
                ->willReturn(true);
            $this->elasticSuiteMock->method('getServers')
                ->willReturn('localhost:9200');
            $elasticSuiteOption = ' --es-hosts=\'localhost:9200\'';
        }

        $adminCredential = $adminEmail
            ? ' --admin-user=\'' . $adminNameExpected . '\''
            . ' --admin-firstname=\'' . $adminFirstnameExpected . '\' --admin-lastname=\'' . $adminLastnameExpected
            . '\' --admin-email=\'' . $adminEmail . '\' --admin-password=\'' . $adminPasswordExpected . '\''
            : '';

        $dbPrefix = isset($mergedConfig['table_prefix']) ? " --db-prefix='" . $mergedConfig['table_prefix'] . "'" : '';

        $expectedCommand =
            'php ./bin/magento setup:install -v -n --ansi --no-interaction --cleanup-database --session-save=\'db\''
            . ' --use-secure-admin=\'1\' --use-rewrites=\'1\' --currency=\'USD\''
            . ' --base-url=\'http://unsecure.url\' --base-url-secure=\'https://secure.url\''
            . ' --backend-frontname=\'' . $adminUrlExpected . '\''
            . ' --language=\'fr_FR\''
            . ' --timezone=\'America/Los_Angeles\' --db-host=\'localhost\' --db-name=\'magento\' --db-user=\'user\''
            . ' --db-password=\'password\''
            . $dbPrefix
            . $adminCredential
            . $elasticSuiteOption;

        self::assertEquals(
            $expectedCommand,
            $this->installCommandFactory->create()
        );
    }

    /**
     * DataProvider for testExecute method.
     *
     * @return array
     */
    public static function executeDataProvider(): array
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
                'elasticSuite' => true,
                'mergedConfig' => [],
            ],
            [
                'adminEmail' => 'admin@example.com',
                'adminName' => '',
                'adminPassword' => '',
                'adminUrl' => '',
                'adminFirstname' => '',
                'adminLastname' => '',
                'adminNameExpected' => AdminDataInterface::DEFAULT_ADMIN_NAME,
                'adminPasswordExpected' => 'generetedPassword',
                'adminUrlExpected' => AdminDataInterface::DEFAULT_ADMIN_URL,
                'adminFirstnameExpected' => AdminDataInterface::DEFAULT_ADMIN_FIRST_NAME,
                'adminLastnameExpected' => AdminDataInterface::DEFAULT_ADMIN_LAST_NAME,
                'elasticSuite' => false,
                'mergedConfig' => ['table_prefix' => 'prefix'],
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
                'elasticSuite' => false,
                'mergedConfig' => [],
            ],
            [
                'adminEmail' => '',
                'adminName' => '',
                'adminPassword' => '',
                'adminUrl' => '',
                'adminFirstname' => '',
                'adminLastname' => '',
                'adminNameExpected' => AdminDataInterface::DEFAULT_ADMIN_NAME,
                'adminPasswordExpected' => 'generetedPassword',
                'adminUrlExpected' => AdminDataInterface::DEFAULT_ADMIN_URL,
                'adminFirstnameExpected' => AdminDataInterface::DEFAULT_ADMIN_FIRST_NAME,
                'adminLastnameExpected' => AdminDataInterface::DEFAULT_ADMIN_LAST_NAME,
                'elasticSuite' => false,
                'mergedConfig' => [],
            ],
        ];
    }

    /**
     * Mock base config method.
     *
     * @param string $adminEmail
     * @param string $adminName
     * @param string $adminPassword
     * @param string $adminUrl
     * @param string $adminFirstname
     * @param string $adminLastname
     * @return void
     */
    private function mockBaseConfig(
        string $adminEmail,
        string $adminName,
        string $adminPassword,
        string $adminUrl,
        string $adminFirstname,
        string $adminLastname
    ): void {
        $this->urlManagerMock->expects(self::once())
            ->method('getUnSecureUrls')
            ->willReturn(['' => 'http://unsecure.url']);
        $this->urlManagerMock->expects(self::once())
            ->method('getSecureUrls')
            ->willReturn(['' => 'https://secure.url']);
        $this->stageConfigMock->expects(self::exactly(2))
            ->method('get')
            ->willReturn(DeployInterface::VAR_VERBOSE_COMMANDS)
            ->willReturn('-v');
        $this->connectionDataMock->expects(self::once())
            ->method('getPassword')
            ->willReturn('password');
        $this->connectionDataMock->expects(self::once())
            ->method('getHost')
            ->willReturn('localhost');
        $this->connectionDataMock->expects(self::once())
            ->method('getDbName')
            ->willReturn('magento');
        $this->connectionDataMock->expects(self::once())
            ->method('getUser')
            ->willReturn('user');
        $this->adminDataMock->method('getLocale')
            ->willReturn('fr_FR');
        $this->adminDataMock->method('getUrl')
            ->willReturn($adminUrl);
        $this->adminDataMock->method('getFirstName')
            ->willReturn($adminFirstname);
        $this->adminDataMock->method('getLastName')
            ->willReturn($adminLastname);
        $this->adminDataMock->method('getEmail')
            ->willReturn($adminEmail);
        $this->adminDataMock->method('getPassword')
            ->willReturn($adminPassword);
        $this->adminDataMock->method('getUsername')
            ->willReturn($adminName);
        $this->adminDataMock->method('getDefaultCurrency')
            ->willReturn('USD');
    }

    /**
     * Test execute with remote storage.
     *
     * @return void
     * @throws ConfigException
     */
    public function testExecuteWithRemoteStorage(): void
    {
        $this->mockBaseConfig('', '', '', '', '', '');
        $this->magentoVersionMock->method('isGreaterOrEqual')
            ->willReturnMap([
                ['2.4.0', false],
                ['2.4.2', true]
            ]);
        $this->remoteStorageMock->method('getDriver')
            ->willReturn('someDriver');
        $this->remoteStorageMock->method('getPrefix')
            ->willReturn('somePrefix');
        $this->remoteStorageMock->method('getConfig')
            ->willReturn([
                'bucket' => 'someBucket',
                'region' => 'someRegion'
            ]);

        self::assertStringContainsString(
            "--remote-storage-prefix='somePrefix' --remote-storage-bucket='someBucket'"
                . " --remote-storage-region='someRegion'",
            $this->installCommandFactory->create()
        );
    }

    /**
     * Test execute with remote storage with keys.
     *
     * @return void
     * @throws ConfigException
     */
    public function testExecuteWithRemoteStorageWithKeys(): void
    {
        $this->mockBaseConfig('', '', '', '', '', '');
        $this->magentoVersionMock->method('isGreaterOrEqual')
            ->willReturnMap([
                ['2.4.0', false],
                ['2.4.2', true]
            ]);
        $this->remoteStorageMock->method('getDriver')
            ->willReturn('someDriver');
        $this->remoteStorageMock->method('getPrefix')
            ->willReturn('somePrefix');
        $this->remoteStorageMock->method('getConfig')
            ->willReturn([
                'bucket' => 'someBucket',
                'region' => 'someRegion',
                'key' => 'someKey',
                'secret' => 'someSecret'
            ]);

        self::assertStringContainsString(
            "--remote-storage-prefix='somePrefix' --remote-storage-bucket='someBucket'"
                . " --remote-storage-region='someRegion'"
                . " --remote-storage-key='someKey' --remote-storage-secret='someSecret'",
            $this->installCommandFactory->create()
        );
    }

    /**
     * Test execute with remote storage with exception.
     *
     * @return void
     * @throws ConfigException
     */
    public function testExecuteWithRemoteStorageWithException(): void
    {
        $this->expectExceptionMessage('Bucket and region are required configurations');
        $this->expectException(ConfigException::class);

        $this->mockBaseConfig('', '', '', '', '', '');
        $this->magentoVersionMock->method('isGreaterOrEqual')
            ->willReturnMap([
                ['2.4.0', false],
                ['2.4.2', true]
            ]);
        $this->remoteStorageMock->method('getDriver')
            ->willReturn('someDriver');
        $this->remoteStorageMock->method('getPrefix')
            ->willReturn('somePrefix');
        $this->remoteStorageMock->method('getConfig')
            ->willReturn([
                'key' => 'someKey',
                'secret' => 'someSecret'
            ]);

        $this->installCommandFactory->create();
    }

    /**
     * Test execute with ES auth options.
     *
     * @return void
     * @throws ConfigException
     */
    public function testExecuteWithESauthOptions(): void
    {
        $this->mockBaseConfig('', '', '', '', '', '');
        $this->magentoVersionMock->method('isGreaterOrEqual')
            ->willReturnMap([
                ['2.4.0', true],
                ['2.4.2', false]
            ]);
        $this->elasticSearchMock->expects($this->any())
            ->method('isInstalled')
            ->willReturn(true);
        $this->elasticSearchMock->expects($this->once())
            ->method('isAuthEnabled')
            ->willReturn(true);
        $this->elasticSearchMock->expects($this->once())
            ->method('getFullEngineName')
            ->willReturn('elasticsearch7');
        $this->elasticSearchMock->expects($this->once())
            ->method('getHost')
            ->willReturn('127.0.0.1');
        $this->elasticSearchMock->expects($this->once())
            ->method('getPort')
            ->willReturn('1234');
        $this->elasticSearchMock->expects($this->once())
            ->method('getConfiguration')
            ->willReturn([
                'host' => '127.0.0.1',
                'port' => '1234',
                'username' => 'user',
                'password' => 'secret',
                'query' => [
                    'index' => 'test'
                ]
            ]);

        $this->openSearchMock->expects($this->any())
            ->method('isInstalled')
            ->willReturn(false);
        $this->openSearchMock->expects($this->never())
            ->method('isAuthEnabled');
        $this->openSearchMock->expects($this->never())
            ->method('getFullEngineName');
        $this->openSearchMock->expects($this->never())
            ->method('getHost');
        $this->openSearchMock->expects($this->never())
            ->method('getPort');
        $this->openSearchMock->expects($this->never())
            ->method('getConfiguration');

        $command = $this->installCommandFactory->create();
        self::assertStringContainsString("--search-engine='elasticsearch7'", $command);
        self::assertStringContainsString("--elasticsearch-enable-auth='1'", $command);
        self::assertStringContainsString("--elasticsearch-username='user'", $command);
        self::assertStringContainsString("--elasticsearch-password='secret'", $command);
        self::assertStringContainsString("--elasticsearch-index-prefix='test'", $command);
    }

    /**
     * Test execute with OS auth options.
     *
     * @param bool $greaterOrEqual
     * @param string $enginePrefixName
     * @dataProvider executeWithOSauthOptionsDataProvider
     * @return void
     * @throws ConfigException
     */
    #[DataProvider('executeWithOSauthOptionsDataProvider')]
    public function testExecuteWithOSauthOptions(
        bool $greaterOrEqual,
        string $enginePrefixName
    ): void {
        $this->mockBaseConfig('', '', '', '', '', '');
        $this->magentoVersionMock->method('isGreaterOrEqual')
            ->willReturnMap([
                ['2.4.0', true],
                ['2.4.2', true],
                ['2.4.4', $greaterOrEqual],
            ]);

        $this->magentoVersionMock->method('satisfies')
            ->willReturnCallback(function (string $constraint) {
                $supportedConstraint = '>=2.3.7-p3 <2.4.0 || >=2.4.3-p2';
                return $constraint === $supportedConstraint;
            });
        $this->openSearchMock->expects($this->any())
            ->method('isInstalled')
            ->willReturn(true);
        $this->openSearchMock->expects($this->once())
            ->method('isAuthEnabled')
            ->willReturn(true);
        $this->openSearchMock->expects($this->once())
            ->method('getFullEngineName')
            ->willReturn('opensearch1');
        $this->openSearchMock->expects($this->once())
            ->method('getHost')
            ->willReturn('127.0.0.1');
        $this->openSearchMock->expects($this->once())
            ->method('getPort')
            ->willReturn('1234');
        $this->openSearchMock->expects($this->once())
            ->method('getConfiguration')
            ->willReturn([
                'host' => '127.0.0.1',
                'port' => '1234',
                'username' => 'user',
                'password' => 'secret',
                'query' => [
                    'index' => 'test'
                ]
            ]);

        $this->elasticSearchMock->expects($this->any())
            ->method('isInstalled')
            ->willReturn(true);
        $this->elasticSearchMock->expects($this->never())
            ->method('isAuthEnabled');
        $this->elasticSearchMock->expects($this->never())
            ->method('getFullEngineName');
        $this->elasticSearchMock->expects($this->never())
            ->method('getHost');
        $this->elasticSearchMock->expects($this->never())
            ->method('getPort');
        $this->elasticSearchMock->expects($this->never())
            ->method('getConfiguration');

        $command = $this->installCommandFactory->create();
        self::assertStringContainsString("--search-engine='opensearch1'", $command);
        self::assertStringContainsString("--" . $enginePrefixName . "-enable-auth='1'", $command);
        self::assertStringContainsString("--" . $enginePrefixName . "-username='user'", $command);
        self::assertStringContainsString("--" . $enginePrefixName . "-password='secret'", $command);
        self::assertStringContainsString("--" . $enginePrefixName . "-index-prefix='test'", $command);
    }

    /**
     * DataProvider for executeWithOSauthOptions method.
     *
     * @return array
     */
    public static function executeWithOSauthOptionsDataProvider(): array
    {
        return [
            [
                false,
                'elasticsearch'
            ]
        ];
    }

    /**
     * Test execute with Amqp config options.
     *
     * @param array $amqpConfig
     * @param string $expectedResult
     * @dataProvider executeWithAmqpConfigOptionsDataProvider
     * @return void
     * @throws ConfigException
     */
    #[DataProvider('executeWithAmqpConfigOptionsDataProvider')]
    public function testExecuteWithAmqpConfigOptions(
        array $amqpConfig,
        string $expectedResult
    ): void {
        $this->mockBaseConfig(
            '',
            '',
            '',
            '',
            '',
            ''
        );
        $this->magentoVersionMock->method('isGreaterOrEqual')
            ->willReturnMap([
                ['2.4.0', false],
                ['2.4.2', true]
            ]);
        $this->amqpConfigMock->method('getConfig')
            ->willReturn($amqpConfig);

        self::assertStringContainsString($expectedResult, $this->installCommandFactory->create());
    }

    /**
     * DataProvider for executeWithAmqpConfigOptions method.
     *
     * @return array
     */
    public static function executeWithAmqpConfigOptionsDataProvider(): array
    {
        return [
            'with all parameters and other config' => [
                'amqpConfig' => [
                    'amqp' => [
                        'host' => 'some_host',
                        'port' => 'some_port',
                        'user' => 'some_user',
                        'password' => 'some_password',
                        'virtualhost' => 'some_host',
                        'some_config' => 'some_config'
                    ],
                    'some_config' => 'some_value',
                ],
                'expectedResult' => "--amqp-host='some_host' --amqp-port='some_port' --amqp-user='some_user'"
                    . " --amqp-password='some_password' --amqp-virtualhost='some_host'",
            ],
            'only host' => [
                'amqpConfig' => [
                    'amqp' => [
                        'host' => 'some_host',
                        'user' => 'some_user',
                        'password' => 'some_password',
                        'virtualhost' => 'some_host',
                        'some_config' => 'some_config'
                    ],
                    'some_config' => 'some_value',
                ],
                'expectedResult' => "--amqp-host='some_host'",
            ],
        ];
    }

    /**
     * Test execute with Amqp config options without host.
     *
     * @param array $amqpConfig
     * @dataProvider executeWithAmqpConfigOptionsWithoutHostDataProvider
     * @return void
     * @throws ConfigException
     *
     */
    #[DataProvider('executeWithAmqpConfigOptionsWithoutHostDataProvider')]
    public function testExecuteWithAmqpConfigOptionsWithoutHost(array $amqpConfig): void
    {
        $this->mockBaseConfig(
            '',
            '',
            '',
            '',
            '',
            ''
        );
        $this->magentoVersionMock->method('isGreaterOrEqual')
            ->willReturnMap([
                ['2.4.0', false],
                ['2.4.2', true]
            ]);
        $this->amqpConfigMock->method('getConfig')
            ->willReturn($amqpConfig);

        $command = $this->installCommandFactory->create();
        self::assertStringNotContainsString('--amqp-host', $command);
        self::assertStringNotContainsString('--amqp-port', $command);
        self::assertStringNotContainsString('--amqp-user', $command);
        self::assertStringNotContainsString('--amqp-password', $command);
        self::assertStringNotContainsString('--amqp-virtualhost', $command);
    }

    /**
     * DataProvider for executeWithAmqpConfigOptionsWithoutHost method.
     *
     * @return array
     */
    public static function executeWithAmqpConfigOptionsWithoutHostDataProvider(): array
    {
        return [
            'host is not set' => [
                'amqpConfig' => [
                    'amqp' => [
                        'port' => 'some_port',
                        'user' => 'some_user',
                        'password' => 'some_password',
                        'virtualhost' => 'some_host',
                        'some_config' => 'some_config'
                    ],
                    'some_config' => 'some_value',
                ],
            ],
            'host is empty' => [
                'amqpConfig' => [
                    'amqp' => [
                        'host' => '',
                        'user' => 'some_user',
                        'password' => 'some_password',
                        'virtualhost' => 'some_host',
                        'some_config' => 'some_config'
                    ],
                    'some_config' => 'some_value',
                ],
            ],
        ];
    }
}
