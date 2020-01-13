<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Test\Unit\Step\Deploy;

use Magento\MagentoCloud\Step\Deploy\SplitDbConnection;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Magento\MagentoCloud\Shell\MagentoShell;
use Magento\MagentoCloud\Filesystem\Flag\Manager as FlagManager;
use Magento\MagentoCloud\Config\Stage\DeployInterface;
use Magento\MagentoCloud\Config\Database\DbConfig;
use Magento\MagentoCloud\Config\Database\ResourceConfig;
use Magento\MagentoCloud\Config\Magento\Env\ReaderInterface as ConfigReader;
use Magento\MagentoCloud\Config\Magento\Env\WriterInterface as ConfigWriter;
use Magento\MagentoCloud\Shell\ProcessInterface;

/**
 * @inheritdoc
 */
class SplitDbConnectionTest extends TestCase
{
    private const CHECKOUT_CONNECTION_CONFIG = [
        'host' => 'checkout.host',
        'dbname' => 'checkout.dbname',
        'username' => 'checkout.username',
        'password' => 'checkout.password',
    ];

    private const SALE_CONNECTION_CONFIG = [
        'host' => 'sale.host',
        'dbname' => 'sale.dbname',
        'username' => 'sale.username',
        'password' => 'sale.password',
    ];

    private const CONNECTION = [
        'checkout' => self::CHECKOUT_CONNECTION_CONFIG,
        'sale' => self::SALE_CONNECTION_CONFIG,
    ];

    private const SLAVE_CHECKOUT_CONNECTION_CONFIG = [
        'host' => 'slave.checkout.host',
        'dbname' => 'slave.checkout.dbname',
        'username' => 'slave.checkout.username',
        'password' => 'slave.checkout.password',
    ];

    private const SLAVE_SALE_CONNECTION_CONFIG = [
        'host' => 'slave.sale.host',
        'dbname' => 'slave.sale.dbname',
        'username' => 'slave.sale.username',
        'password' => 'slave.sale.password',
    ];

    private const SLAVE_CONNECTION = [
        'checkout' => self::SLAVE_CHECKOUT_CONNECTION_CONFIG,
        'sale' => self::SLAVE_SALE_CONNECTION_CONFIG,
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
     * @var ResourceConfig|MockObject
     */
    private $resourceConfigMock;

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
     * @var ConfigWriter|MockObject
     */
    private $configWriterMock;

    /**
     * @var MagentoShell|MockObject
     */
    private $magentoShellMock;

    /**
     * @var ProcessInterface|MockObject
     */
    private $processMock;

    /**
     * @var SplitDbConnection
     */
    private $step;

    /**
     * {@inheritdoc}
     *
     * @throws \ReflectionException
     */
    protected function setUp()
    {
        $this->stageConfigMock = $this->getMockForAbstractClass(DeployInterface::class);
        $this->dbConfigMock = $this->createMock(DbConfig::class);
        $this->resourceConfigMock = $this->createMock(ResourceConfig::class);
        $this->loggerMock = $this->getMockForAbstractClass(LoggerInterface::class);
        $this->flagManagerMock = $this->createMock(FlagManager::class);
        $this->configReaderMock = $this->createMock(ConfigReader::class);
        $this->configWriterMock = $this->createMock(ConfigWriter::class);
        $this->magentoShellMock = $this->createMock(MagentoShell::class);
        $this->processMock = $this->getMockForAbstractClass(ProcessInterface::class);

        $this->step = new SplitDbConnection(
            $this->stageConfigMock,
            $this->dbConfigMock,
            $this->resourceConfigMock,
            $this->loggerMock,
            $this->flagManagerMock,
            $this->configReaderMock,
            $this->configWriterMock,
            $this->magentoShellMock
        );
    }

    /**
     * Variable SPLIT_DB is a empty array
     */
    public function testExecuteVarSplitDbIsEmpty()
    {
        $this->stageConfigMock->expects($this->once())
            ->method('get')
            ->with(DeployInterface::VAR_SPLIT_DB)
            ->willReturn([]);
        $this->dbConfigMock->expects($this->once())
            ->method('get')
            ->willReturn([
                'connection' => [
                    'checkout' => [],
                    'sale' => [],
                ]
            ]);
        $this->configReaderMock->expects($this->once())
            ->method('read')
            ->willReturn([
                'db' => [
                    'connection' => [
                        'checkout' => [],
                        'sale' => [],
                    ]
                ]
            ]);
        $this->loggerMock->expects($this->once())
            ->method('warning')
            ->with('Variable SPLIT_DB does not have data which were already split types: sales, quote');

        $this->magentoShellMock->expects($this->never())
            ->method('execute');
        $this->configWriterMock->expects($this->never())
            ->method('create');

        $this->step->execute();
    }

    /**
     * Variable SPLIT_DB is not empty and the flag IGNORES_SPLIT_DB exists
     */
    public function testExecuteVarSplitDbIsNotEmptyAndFlagIgnoreSplitDbExists()
    {
        $this->stageConfigMock->expects($this->once())
            ->method('get')
            ->with(DeployInterface::VAR_SPLIT_DB)
            ->willReturn(DeployInterface::SPLIT_DB_VALUES);
        $this->flagManagerMock->expects($this->once())
            ->method('exists')
            ->with(FlagManager::FLAG_IGNORE_SPLIT_DB)
            ->willReturn(true);
        $this->loggerMock->expects($this->once())
            ->method('info')
            ->with('Enabling a split database will be skipped. The flag ignore_split_db was detected.');
        $this->magentoShellMock->expects($this->never())
            ->method('execute');
        $this->configWriterMock->expects($this->never())
            ->method('create');

        $this->step->execute();
    }

    /**
     * Relationships have no connections for split database
     *
     * @param array $dbConfig
     * @param array $splitTypes
     * @throws \Magento\MagentoCloud\Step\StepException
     * @dataProvider  dataProviderExecuteRelationshipNotHaveConfigurations
     */
    public function testExecuteRelationshipNotHaveConfigurations(array $dbConfig, array $splitTypes)
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
            ->willReturn($dbConfig);
        $this->loggerMock->expects($this->once())
            ->method('error')
            ->with(
                'Enabling a split database will be skipped.'
                . ' Relationship do not have configuration for next types: ' . implode(', ', $splitTypes)
            );
        $this->magentoShellMock->expects($this->never())
            ->method('execute');
        $this->configWriterMock->expects($this->never())
            ->method('create');

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
                ['connection' => ['sale' => []]],
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
                'Variable SPLIT_DB does not have data which were already split types: '
                . implode(', ', $splitTypes)
            );
        $this->magentoShellMock->expects($this->never())
            ->method('execute');
        $this->configWriterMock->expects($this->never())
            ->method('create');

        $this->step->execute();
    }

    /**
     * DataProvider for testExecuteVarSplitDbDoesNotHaveSplitTypes
     */
    public function dataProviderExecuteVarSplitDbDoesNotHaveSplitTypes()
    {
        return [
            [
                ['quote'],
                ['connection' => ['checkout' => []]],
                ['db' => ['connection' => ['sale' => []]]],
                ['sales']
            ],

            [
                ['sales'],
                ['connection' => ['sale' => []]],
                ['db' => ['connection' => ['checkout' => []]]],
                ['quote']
            ]
        ];
    }

    /**
     * Split db will be enabled without slave connections
     */
    public function testExecuteEnableSplitDbWithoutSlaveConnection()
    {
        $this->stageConfigMock->expects($this->exactly(2))
            ->method('get')
            ->withConsecutive(
                [DeployInterface::VAR_SPLIT_DB],
                [DeployInterface::VAR_MYSQL_USE_SLAVE_CONNECTION]
            )
            ->willReturnOnConsecutiveCalls(
                DeployInterface::SPLIT_DB_VALUES,
                false
            );
        $this->flagManagerMock->expects($this->once())
            ->method('exists')
            ->with(FlagManager::FLAG_IGNORE_SPLIT_DB)
            ->willReturn(false);
        $this->dbConfigMock->expects($this->once())
            ->method('get')
            ->willReturn(['connection' => self::CONNECTION]);
        $this->configReaderMock->expects($this->once())
            ->method('read')
            ->willReturn(['db' => ['connection' => []]]);
        $this->processMock->expects($this->exactly(2))
            ->method('getOutput')
            ->willReturnOnConsecutiveCalls(
                'Some output about split quote',
                'Some output about split sales'
            );
        $this->magentoShellMock->expects($this->exactly(2))
            ->method('execute')
            ->withConsecutive(
                ['setup:db-schema:split-quote --host="checkout.host" --dbname="checkout.dbname"'
                    . ' --username="checkout.username" --password="checkout.password"'],
                ['setup:db-schema:split-sales --host="sale.host" --dbname="sale.dbname"'
                    . ' --username="sale.username" --password="sale.password"']
            )
            ->willReturn($this->processMock);
        $this->loggerMock->expects($this->exactly(2))
            ->method('debug')
            ->withConsecutive(
                ['Some output about split quote'],
                ['Some output about split sales']
            );
        $this->loggerMock->expects($this->exactly(2))
            ->method('info')
            ->withConsecutive(
                ['Quote tables were split to DB checkout.dbname in checkout.host'],
                ['Sales tables were split to DB sale.dbname in sale.host']
            );

        $this->configWriterMock->expects($this->never())
            ->method('create');

        $this->step->execute();
    }

    /**
     * Case when split db will be enabled with slave connections
     */
    public function testExecuteEnableSplitDbWithSlaveConnections()
    {
        $this->stageConfigMock->expects($this->exactly(2))
            ->method('get')
            ->withConsecutive(
                [DeployInterface::VAR_SPLIT_DB],
                [DeployInterface::VAR_MYSQL_USE_SLAVE_CONNECTION]
            )
            ->willReturnOnConsecutiveCalls(
                DeployInterface::SPLIT_DB_VALUES,
                true
            );
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
        $this->configReaderMock->expects($this->exactly(2))
            ->method('read')
            ->willReturnOnConsecutiveCalls(
                ['db' => ['connection' => []]],
                ['db' => ['connection' => self::CONNECTION]]
            );
        $this->processMock->expects($this->exactly(2))
            ->method('getOutput')
            ->willReturnOnConsecutiveCalls(
                'Some output about split quote',
                'Some output about split sales'
            );
        $this->magentoShellMock->expects($this->exactly(2))
            ->method('execute')
            ->withConsecutive(
                ['setup:db-schema:split-quote --host="checkout.host" --dbname="checkout.dbname"'
                    . ' --username="checkout.username" --password="checkout.password"'],
                ['setup:db-schema:split-sales --host="sale.host" --dbname="sale.dbname"'
                    . ' --username="sale.username" --password="sale.password"']
            )
            ->willReturn($this->processMock);
        $this->loggerMock->expects($this->exactly(2))
            ->method('debug')
            ->withConsecutive(
                ['Some output about split quote'],
                ['Some output about split sales']
            );
        $this->loggerMock->expects($this->exactly(4))
            ->method('info')
            ->withConsecutive(
                ['Quote tables were split to DB checkout.dbname in checkout.host'],
                ['Sales tables were split to DB sale.dbname in sale.host'],
                ['Slave connection for checkout connection was set'],
                ['Slave connection for sale connection was set']
            );

        $this->configWriterMock->expects($this->once())
            ->method('create')
            ->with(['db' => [
                'connection' => self::CONNECTION,
                'slave_connection' => self::SLAVE_CONNECTION
            ]]);

        $this->step->execute();
    }

    /**
     * Case when enable slave connections only
     *
     * @throws \Magento\MagentoCloud\Step\StepException
     */
    public function testExecuteEnableSlaveConnectionsOnly()
    {
        $this->stageConfigMock->expects($this->exactly(2))
            ->method('get')
            ->withConsecutive(
                [DeployInterface::VAR_SPLIT_DB],
                [DeployInterface::VAR_MYSQL_USE_SLAVE_CONNECTION]
            )
            ->willReturnOnConsecutiveCalls(
                DeployInterface::SPLIT_DB_VALUES,
                true
            );
        $this->flagManagerMock->expects($this->once())
            ->method('exists')
            ->with(FlagManager::FLAG_IGNORE_SPLIT_DB)
            ->willReturn(false);
        $this->dbConfigMock->expects($this->once())
            ->method('get')
            ->willReturn([
                'connection' => self::CONNECTION,
                'slave_connection' => self::SLAVE_CONNECTION,
            ]);
        $this->configReaderMock->expects($this->exactly(2))
            ->method('read')
            ->willReturn([
                'db' => [
                    'connection' => self::CONNECTION,
                ]
            ]);
        $this->magentoShellMock->expects($this->never())
            ->method('execute');

        $this->configWriterMock->expects($this->once())
            ->method('create')
            ->with(['db' => [
                'connection' => self::CONNECTION,
                'slave_connection' => self::SLAVE_CONNECTION,
            ]]);

        $this->step->execute();
    }
}
