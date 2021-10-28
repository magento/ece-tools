<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Test\Unit\Step\Deploy;

use Magento\MagentoCloud\App\Error;
use Magento\MagentoCloud\Filesystem\FileSystemException;
use Magento\MagentoCloud\Step\Deploy\SplitDbConnection;
use Magento\MagentoCloud\Step\Deploy\SplitDbConnection\SlaveConnection;
use Magento\MagentoCloud\Step\StepException;
use Magento\MagentoCloud\Util\UpgradeProcess;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Magento\MagentoCloud\Shell\MagentoShell;
use Magento\MagentoCloud\Filesystem\Flag\Manager as FlagManager;
use Magento\MagentoCloud\Config\Stage\DeployInterface;
use Magento\MagentoCloud\Config\Database\DbConfig;
use Magento\MagentoCloud\Config\Magento\Env\ReaderInterface as ConfigReader;

/**
 * @inheritdoc
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class SplitDbConnectionTest extends TestCase
{
    private const CHECKOUT_CONNECTION_CONFIG = [
        'host' => 'checkout.host',
        'dbname' => 'checkout.dbname',
        'username' => 'checkout.username',
        'password' => 'checkout.password',
    ];

    private const SALES_CONNECTION_CONFIG = [
        'host' => 'sales.host',
        'dbname' => 'sales.dbname',
        'username' => 'sales.username',
        'password' => 'sales.password',
    ];

    private const CONNECTION = [
        'checkout' => self::CHECKOUT_CONNECTION_CONFIG,
        'sales' => self::SALES_CONNECTION_CONFIG,
    ];

    private const SLAVE_CHECKOUT_CONNECTION_CONFIG = [
        'host' => 'slave.checkout.host',
        'dbname' => 'slave.checkout.dbname',
        'username' => 'slave.checkout.username',
        'password' => 'slave.checkout.password',
    ];

    private const SLAVE_SALES_CONNECTION_CONFIG = [
        'host' => 'slave.sales.host',
        'dbname' => 'slave.sales.dbname',
        'username' => 'slave.sales.username',
        'password' => 'slave.sales.password',
    ];

    private const SLAVE_CONNECTION = [
        'checkout' => self::SLAVE_CHECKOUT_CONNECTION_CONFIG,
        'sales' => self::SLAVE_SALES_CONNECTION_CONFIG,
    ];

    /**
     * @var LoggerInterface|MockObject
     */
    private $loggerMock;

    /**
     * @var DbConfig|MockObject
     */
    private $dbConfigMock;

    /**
     * @var DeployInterface|MockObject
     */
    private $stageConfigMock;

    /**
     * @var FlagManager|MockObject
     */
    private $flagManagerMock;

    /**
     * @var ConfigReader|MockObject
     */
    private $configReaderMock;

    /**
     * @var MagentoShell|MockObject
     */
    private $magentoShellMock;

    /**
     * @var UpgradeProcess|MockObject
     */
    private $upgradeProcessMock;

    /**
     * @var SlaveConnection|MockObject
     */
    private $slaveConnectionMock;

    /**
     * @var SplitDbConnection
     */
    private $step;

    /**
     * {@inheritdoc}
     *
     * @throws \ReflectionException
     */
    protected function setUp(): void
    {
        $this->stageConfigMock = $this->getMockForAbstractClass(DeployInterface::class);
        $this->dbConfigMock = $this->createMock(DbConfig::class);
        $this->loggerMock = $this->getMockForAbstractClass(LoggerInterface::class);
        $this->flagManagerMock = $this->createMock(FlagManager::class);
        $this->configReaderMock = $this->createMock(ConfigReader::class);
        $this->magentoShellMock = $this->createMock(MagentoShell::class);
        $this->upgradeProcessMock = $this->createMock(UpgradeProcess::class);
        $this->slaveConnectionMock = $this->createMock(SlaveConnection::class);

        $this->step = new SplitDbConnection(
            $this->stageConfigMock,
            $this->dbConfigMock,
            $this->loggerMock,
            $this->flagManagerMock,
            $this->configReaderMock,
            $this->magentoShellMock,
            $this->upgradeProcessMock,
            $this->slaveConnectionMock
        );
    }

    /**
     * Flag IGNORES_SPLIT_DB exists
     */
    public function testExecuteFlagIgnoreSplitDbExists()
    {
        $this->flagManagerMock->expects($this->once())
            ->method('exists')
            ->with(FlagManager::FLAG_IGNORE_SPLIT_DB)
            ->willReturn(true);
        $this->loggerMock->expects($this->once())
            ->method('info')
            ->with('Enabling a split database will be skipped. The flag ignore_split_db was detected.');
        $this->flagManagerMock->expects($this->once())
            ->method('delete')
            ->with(FlagManager::FLAG_IGNORE_SPLIT_DB);
        $this->magentoShellMock->expects($this->never())
            ->method('execute');
        $this->upgradeProcessMock->expects($this->never())
            ->method('execute');
        $this->slaveConnectionMock->expects($this->never())
            ->method('update');

        $this->step->execute();
    }

    /**
     * Relationships have no connections for split database
     *
     * @param array $dbConfig
     * @param array $splitTypes
     * @dataProvider  dataProviderExecuteRelationshipNotHaveConfigurations
     */
    public function testExecuteRelationshipNotHaveConfigurations(array $dbConfig, array $splitTypes)
    {
        $this->flagManagerMock->expects($this->once())
            ->method('exists')
            ->with(FlagManager::FLAG_IGNORE_SPLIT_DB)
            ->willReturn(false);
        $this->stageConfigMock->expects($this->once())
            ->method('get')
            ->with(DeployInterface::VAR_SPLIT_DB)
            ->willReturn(DeployInterface::SPLIT_DB_VALUES);
        $this->dbConfigMock->expects($this->once())
            ->method('get')
            ->willReturn($dbConfig);
        $this->loggerMock->expects($this->once())
            ->method('warning')
            ->with(
                'Enabling a split database will be skipped.'
                . ' Relationship do not have configuration for next types: ' . implode(', ', $splitTypes)
            );
        $this->magentoShellMock->expects($this->never())
            ->method('execute');
        $this->upgradeProcessMock->expects($this->never())
            ->method('execute');
        $this->slaveConnectionMock->expects($this->never())
            ->method('update');

        $this->step->execute();
    }

    /**
     * DataProvider for testExecuteWhenRelationshipNotHaveConfigurations
     * @return array
     */
    public function dataProviderExecuteRelationshipNotHaveConfigurations(): array
    {
        return [
            [
                [],
                ['sales', 'quote'],
            ],
            [
                ['connection' => ['sales' => []]],
                ['quote'],
            ],
            [
                ['connection' => ['checkout' => []]],
                ['sales'],
            ]
        ];
    }

    /**
     * Variable SPLIT_DB does not have some split type connections which exists in env.php
     *
     * @param array $varSplitDb
     * @param array $dbConfig
     * @param array $mageConfig
     * @param array $splitTypes
     * @dataProvider dataProviderExecuteVarSplitDbDoesNotHaveSplitTypes
     */
    public function testExecuteVarSplitDbDoesNotHaveSplitTypes(
        array $varSplitDb,
        array $dbConfig,
        array $mageConfig,
        array $splitTypes
    ) {
        $this->stageConfigMock->expects($this->once())
            ->method('get')
            ->with(DeployInterface::VAR_SPLIT_DB)
            ->willReturn($varSplitDb);
        $this->flagManagerMock->expects($this->once())
            ->method('exists')
            ->with(FlagManager::FLAG_IGNORE_SPLIT_DB)
            ->willReturn(false);
        $this->dbConfigMock->expects($this->once())
            ->method('get')
            ->willReturn($dbConfig);
        $this->configReaderMock->expects($this->once())
            ->method('read')
            ->willReturn($mageConfig);
        $this->loggerMock->expects($this->once())
            ->method('warning')
            ->with(
                'The SPLIT_DB variable is missing the configuration for split connection types: '
                . implode(', ', $splitTypes)
            );
        $this->magentoShellMock->expects($this->never())
            ->method('execute');
        $this->upgradeProcessMock->expects($this->never())
            ->method('execute');
        $this->slaveConnectionMock->expects($this->never())
            ->method('update');

        $this->step->execute();
    }

    /**
     * DataProvider for testExecuteVarSplitDbDoesNotHaveSplitTypes
     */
    public function dataProviderExecuteVarSplitDbDoesNotHaveSplitTypes()
    {
        return [
            [
                'varSplitDb' => [],
                'dbConfig' => [],
                'mageConfig' => [
                    'db' => [
                        'connection' => [
                            'sales' => [],
                            'checkout' => []
                        ]
                    ]
                ],
                'splitTypes' => ['sales', 'quote']
            ],
            [
                'varSplitDb' => [],
                'dbConfig' => [
                    'connection' => [
                        'sales' => [],
                        'checkout' => []
                    ]
                ],
                'mageConfig' => [
                    'db' => [
                        'connection' => [
                            'sales' => [],
                            'checkout' => []
                        ]
                    ]
                ],
                'splitTypes' => ['sales', 'quote']
            ],
            [
                'varSplitDb' => ['quote'],
                'dbConfig' => ['connection' => ['checkout' => []]],
                'mageConfig' => ['db' => ['connection' => ['sales' => []]]],
                'splitTypes' => ['sales']
            ],
            [
                'varSplitDb' => ['sales'],
                'dbConfig' => ['connection' => ['sales' => []]],
                'mageConfig' => ['db' => ['connection' => ['checkout' => []]]],
                'splitTypes' => ['quote']
            ],
        ];
    }

    /**
     * Split db will be enabled with slave connections
     */
    public function testExecuteEnableSplitDbWithSlaveConnection()
    {
        $this->flagManagerMock->expects($this->once())
            ->method('exists')
            ->with(FlagManager::FLAG_IGNORE_SPLIT_DB)
            ->willReturn(false);
        $this->stageConfigMock->expects($this->once())
            ->method('get')
            ->with(DeployInterface::VAR_SPLIT_DB)
            ->willReturn(DeployInterface::SPLIT_DB_VALUES);
        $this->dbConfigMock->expects($this->once())
            ->method('get')
            ->willReturn(['connection' => self::CONNECTION]);
        $this->configReaderMock->expects($this->once())
            ->method('read')
            ->willReturn(['db' => ['connection' => []]]);
        $this->magentoShellMock->expects($this->exactly(2))
            ->method('execute')
            ->withConsecutive(
                ['setup:db-schema:split-quote --host="checkout.host" --dbname="checkout.dbname"'
                    . ' --username="checkout.username" --password="checkout.password"'],
                ['setup:db-schema:split-sales --host="sales.host" --dbname="sales.dbname"'
                    . ' --username="sales.username" --password="sales.password"']
            );
        $this->upgradeProcessMock->expects($this->exactly(2))
            ->method('execute');
        $this->loggerMock->expects($this->exactly(2))
            ->method('info')
            ->withConsecutive(
                ['Quote tables were split to DB checkout.dbname in checkout.host'],
                ['Sales tables were split to DB sales.dbname in sales.host']
            );
        $this->slaveConnectionMock->expects($this->once())
            ->method('update');

        $this->step->execute();
    }

    /**
     * Case when enable slave connections only
     */
    public function testExecuteOnlyUpdateSlaveConnections()
    {
        $this->stageConfigMock->expects($this->once())
            ->method('get')
            ->with(DeployInterface::VAR_SPLIT_DB)
            ->willReturn(DeployInterface::SPLIT_DB_VALUES);
        $this->flagManagerMock->expects($this->once())
            ->method('exists')
            ->with(FlagManager::FLAG_IGNORE_SPLIT_DB)
            ->willReturn(false);
        $this->dbConfigMock->expects($this->once())
            ->method('get')
            ->willReturn([
                'connection' => self::CONNECTION,
                'slave_connection' => self::SLAVE_CONNECTION
            ]);
        $this->configReaderMock->expects($this->once())
            ->method('read')
            ->willReturn(['db' => ['connection' => self::CONNECTION]]);
        $this->magentoShellMock->expects($this->never())
            ->method('execute');
        $this->upgradeProcessMock->expects($this->never())
            ->method('execute');
        $this->slaveConnectionMock->expects($this->once())
            ->method('update');

        $this->step->execute();
    }

    public function testExecuteWithFileSystemExceptionInRead()
    {
        $errorMsg = 'Some error';
        $errorCode = 111;
        $exception = new FileSystemException($errorMsg, $errorCode);
        $this->expectException(StepException::class);
        $this->expectExceptionCode($errorCode);
        $this->expectExceptionMessage($errorMsg);

        $this->flagManagerMock->expects($this->once())
            ->method('exists')
            ->with(FlagManager::FLAG_IGNORE_SPLIT_DB)
            ->willReturn(false);

        $this->stageConfigMock->expects($this->once())
            ->method('get')
            ->with(DeployInterface::VAR_SPLIT_DB)
            ->willReturn(DeployInterface::SPLIT_DB_VALUES);

        $this->dbConfigMock->expects($this->once())
            ->method('get')
            ->willReturn(['connection' => self::CONNECTION]);

        $this->configReaderMock->expects($this->once())
            ->method('read')
            ->willThrowException($exception);

        $this->step->execute();
    }

    public function testExecuteWithFileSystemExceptionInSlaveUpdate()
    {
        $errorMsg = 'Some error';
        $errorCode = 111;
        $exception = new FileSystemException($errorMsg, $errorCode);
        $this->expectException(StepException::class);
        $this->expectExceptionCode(Error::DEPLOY_ENV_PHP_IS_NOT_WRITABLE);
        $this->expectExceptionMessage('Cannot write slave connection(s) to the `./app/etc/env.php`');

        $this->flagManagerMock->expects($this->once())
            ->method('exists')
            ->with(FlagManager::FLAG_IGNORE_SPLIT_DB)
            ->willReturn(false);

        $this->stageConfigMock->expects($this->once())
            ->method('get')
            ->with(DeployInterface::VAR_SPLIT_DB)
            ->willReturn(DeployInterface::SPLIT_DB_VALUES);

        $this->dbConfigMock->expects($this->once())
            ->method('get')
            ->willReturn(['connection' => self::CONNECTION]);

        $this->configReaderMock->expects($this->once())
            ->method('read')
            ->willReturn(['db' => ['connection' => []]]);

        $this->slaveConnectionMock->expects($this->once())
            ->method('update')
            ->willThrowException($exception);

        $this->step->execute();
    }
}
