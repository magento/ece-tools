<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Test\Unit\Step\Deploy\InstallUpdate\ConfigUpdate;

use Magento\MagentoCloud\Config\ConfigMerger;
use Magento\MagentoCloud\Config\Magento\Env\ReaderInterface as ConfigReader;
use Magento\MagentoCloud\Config\Magento\Env\WriterInterface as ConfigWriter;
use Magento\MagentoCloud\Config\Database\DbConfig;
use Magento\MagentoCloud\Config\Database\ResourceConfig;
use Magento\MagentoCloud\Config\Stage\DeployInterface;
use Magento\MagentoCloud\DB\Data\ConnectionInterface;
use Magento\MagentoCloud\DB\Data\RelationshipConnectionFactory;
use Magento\MagentoCloud\Step\Deploy\InstallUpdate\ConfigUpdate\DbConnection;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Magento\MagentoCloud\Filesystem\Flag\Manager as FlagManager;

/**
 * @inheritdoc
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class DbConnectionTest extends TestCase
{
    private const DEFAULT_CONNECTION = [
        'host' => 'host',
        'dbname' => 'dbname',
        'password' => 'password',
        'username' => 'username',
    ];

    private const CUSTOM_CONNECTION = [
        'host' => 'custom.host',
        'dbname' => 'custom.dbname',
        'password' => 'custom.password',
        'username' => 'custom.username',
    ];

    private const CHECKOUT_CONNECTION = [
        'host' => 'checkout.host',
        'dbname' => 'checkout.dbname',
        'password' => 'checkout.password',
        'username' => 'checkout.username',
    ];

    private const SALES_CONNECTION = [
        'host' => 'sales.host',
        'dbname' => 'sales.dbname',
        'password' => 'sales.password',
        'username' => 'sales.username',
    ];

    private const SLAVE_DEFAULT_CONNECTION = [
        'host' => 'slave.host',
        'dbname' => 'slave.dbname',
        'password' => 'slave.password',
        'username' => 'slave.username',
    ];

    private const SLAVE_CHECKOUT_CONNECTION = [
        'host' => 'slave.checkout.host',
        'dbname' => 'slave.checkout.dbname',
        'password' => 'slave.checkout.password',
        'username' => 'slave.checkout.username',
    ];

    private const SLAVE_SALE_CONNECTION = [
        'host' => 'slave.sales.host',
        'dbname' => 'slave.sales.dbname',
        'password' => 'slave.sales.password',
        'username' => 'slave.sales.username',
    ];

    private const RESOURCE_DEFAULT_SETUP = ['connection' => 'default'];
    private const RESOURCE_CHECKOUT = ['connection' => 'checkout'];
    private const RESOURCE_SALE = ['connection' => 'sales'];
    private const RESOURCE_CUSTOM = ['connection' => 'custom'];

    /**
     * @var DeployInterface|MockObject
     */
    private $stageConfigMock;

    /**
     * @var LoggerInterface|MockObject
     */
    private $loggerMock;

    /**
     * @var ConfigWriter|MockObject
     */
    private $configWriterMock;

    /**
     * @var ConfigReader|MockObject
     */
    private $configReaderMock;

    /**
     * @var DbConfig|MockObject
     */
    private $dbConfigMock;

    /**
     * @var ResourceConfig|MockObject
     */
    private $resourceConfigMock;

    /**
     * @var RelationshipConnectionFactory|MockObject
     */
    private $envConnectionDataFactoryMock;

    /**
     * @var ConnectionInterface|MockObject
     */
    private $envConnectionDataMock;

    /**
     * @var FlagManager|MockObject
     */
    private $flagManagerMock;

    /**
     * @var DbConnection
     */
    private $step;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->stageConfigMock = $this->getMockForAbstractClass(DeployInterface::class);
        $this->dbConfigMock = $this->createMock(DbConfig::class);
        $this->resourceConfigMock = $this->createMock(ResourceConfig::class);
        $this->configWriterMock = $this->createMock(ConfigWriter::class);
        $this->configReaderMock = $this->createMock(ConfigReader::class);
        $this->envConnectionDataFactoryMock = $this->createMock(RelationshipConnectionFactory::class);
        $this->loggerMock = $this->getMockForAbstractClass(LoggerInterface::class);
        $this->flagManagerMock = $this->createMock(FlagManager::class);

        $this->envConnectionDataMock = $this->getMockForAbstractClass(ConnectionInterface::class);
        $this->envConnectionDataFactoryMock->expects($this->any())
            ->method('create')
            ->willReturn($this->envConnectionDataMock);

        $this->step = new DbConnection(
            $this->stageConfigMock,
            $this->dbConfigMock,
            $this->resourceConfigMock,
            $this->configWriterMock,
            $this->configReaderMock,
            new ConfigMerger(),
            $this->envConnectionDataFactoryMock,
            $this->loggerMock,
            $this->flagManagerMock
        );
    }

    /**
     * Case when an environment has no database configuration
     */
    public function testExecuteWithoutDbConfigInEnvironment()
    {
        $this->dbConfigMock->expects($this->once())
            ->method('get')
            ->willReturn([]);
        $this->loggerMock->expects($this->once())
            ->method('notice')
            ->with('Database relationship configuration does not exist'
                . ' and database is not configured through .magento.env.yaml or env variable.'
                . ' Will be applied the previous database configuration.');
        $this->flagManagerMock->expects($this->never())
            ->method('set');
        $this->configWriterMock->expects($this->never())
            ->method('create');
        $this->step->execute();
    }

    /**
     * Case when slave connections and split database are not used
     *
     * @param $mageConfig array
     * @param $envConfig array
     * @param $expectedResult array
     * @dataProvider executeWithoutSplitAndSlaveConfigDataProvider
     */
    public function testExecuteWithoutSplitAndSlaveConfig(array $mageConfig, array $envConfig, array $expectedResult)
    {
        $resourceConfig = [
            'default_setup' => self::RESOURCE_DEFAULT_SETUP,
        ];
        $expectedResult['resource'] = $resourceConfig;

        $this->dbConfigMock->expects($this->once())
            ->method('get')
            ->willReturn($envConfig);
        $this->loggerMock->expects($this->once())
            ->method('info')
            ->with('Updating env.php DB connection configuration.');
        $this->configReaderMock->expects($this->once())
            ->method('read')
            ->willReturn($mageConfig);
        $this->resourceConfigMock->expects($this->once())
            ->method('get')
            ->willReturn($resourceConfig);
        $this->stageConfigMock->expects($this->once())
            ->method('get')
            ->with(DeployInterface::VAR_MYSQL_USE_SLAVE_CONNECTION)
            ->willReturnOnConsecutiveCalls(false, []);
        $this->configWriterMock->expects($this->once())
            ->method('create')
            ->with($expectedResult);
        $this->flagManagerMock->expects($this->never())
            ->method('set');

        $this->step->execute();
    }

    /**
     * @return array
     */
    public function executeWithoutSplitAndSlaveConfigDataProvider()
    {
        $dbConfig = [
            'connection' => [
                'default' => self::DEFAULT_CONNECTION,
                'indexer' => self::DEFAULT_CONNECTION,
            ]
        ];
        $newConnection = array_merge(self::DEFAULT_CONNECTION, ['host' => 'host2']);
        $newBbConfig = [
            'connection' => [
                'default' => $newConnection,
                'indexer' => $newConnection,
            ]
        ];

        return [
            [
                ['db' => $dbConfig],
                $dbConfig,
                ['db' => $dbConfig],
            ],
            'changed connection' => [
                ['db' => $dbConfig],
                $newBbConfig,
                ['db' => $newBbConfig],
            ],
            'no data before' => [
                [],
                $dbConfig,
                ['db' => $dbConfig],
            ],
        ];
    }

    /**
     * Case with slave connections and without split config
     */
    public function testExecuteWithSlaveWithoutSplitConfigs()
    {
        $this->dbConfigMock->expects($this->once())
            ->method('get')
            ->willReturn([
                'connection' => ['default' => self::DEFAULT_CONNECTION],
                'slave_connection' => ['default' => self::SLAVE_DEFAULT_CONNECTION],
            ]);
        $this->loggerMock->expects($this->exactly(2))
            ->method('info')
            ->withConsecutive(
                ['Updating env.php DB connection configuration.'],
                ['Set DB slave connection for default connection.']
            );
        $this->configReaderMock->expects($this->once())
            ->method('read')
            ->willReturn([
                'db' => ['connection' => ['default' => self::DEFAULT_CONNECTION]],
                'resource' => ['default_setup' => self::RESOURCE_DEFAULT_SETUP]
            ]);
        $this->resourceConfigMock->expects($this->once())
            ->method('get')
            ->willReturn(['default_setup' => self::RESOURCE_DEFAULT_SETUP]);
        $this->loggerMock->expects($this->never())
            ->method('warning');
        $this->stageConfigMock->expects($this->exactly(2))
            ->method('get')
            ->withConsecutive(
                [DeployInterface::VAR_MYSQL_USE_SLAVE_CONNECTION],
                [DeployInterface::VAR_DATABASE_CONFIGURATION]
            )
            ->willReturnOnConsecutiveCalls(true, []);
        $this->envConnectionDataMock->expects($this->once())
            ->method('getHost')
            ->willReturn('host');
        $this->dbConfigMock->expects($this->once())
            ->method('isCustomConnectionCompatibleForSlave')
            ->with([], 'default', $this->envConnectionDataMock)
            ->willReturn(true);
        $this->configWriterMock->expects($this->once())
            ->method('create')
            ->with([
                'db' => [
                    'connection' => ['default' => self::DEFAULT_CONNECTION],
                    'slave_connection' => ['default' => self::SLAVE_DEFAULT_CONNECTION]
                ],
                'resource' => ['default_setup' => self::RESOURCE_DEFAULT_SETUP]
            ]);
        $this->flagManagerMock->expects($this->never())
            ->method('set');

        $this->step->execute();
    }

    /**
     * Case when with not compatible database settings for slave connection
     */
    public function testExecuteWithNotCompatibleDatabaseConfigForSlaveConnection()
    {
        $resourceConfig = [
            'default_setup' => self::RESOURCE_DEFAULT_SETUP,
        ];

        $dbConfig = [
            'connection' => [
                'default' => self::DEFAULT_CONNECTION,
            ]
        ];
        $this->dbConfigMock->expects($this->once())
            ->method('get')
            ->willReturn($dbConfig);
        $this->loggerMock->expects($this->once())
            ->method('info')
            ->with('Updating env.php DB connection configuration.');
        $this->configReaderMock->expects($this->once())
            ->method('read')
            ->willReturn([
                'db' => $dbConfig,
                'resource' => $resourceConfig
            ]);
        $this->resourceConfigMock->expects($this->once())
            ->method('get')
            ->willReturn($resourceConfig);
        $this->stageConfigMock->expects($this->exactly(2))
            ->method('get')
            ->withConsecutive(
                [DeployInterface::VAR_MYSQL_USE_SLAVE_CONNECTION],
                [DeployInterface::VAR_DATABASE_CONFIGURATION]
            )
            ->willReturnOnConsecutiveCalls(true, []);
        $this->envConnectionDataMock->expects($this->once())
            ->method('getHost')
            ->willReturn('host');
        $this->dbConfigMock->expects($this->once())
            ->method('isCustomConnectionCompatibleForSlave')
            ->with([], 'default', $this->envConnectionDataMock)
            ->willReturn(false);
        $this->loggerMock->expects($this->once())
            ->method('warning')
            ->with('You have changed db configuration that not compatible with default slave connection.');
        $this->configWriterMock->expects($this->once())
            ->method('create')
            ->with([
                'db' => $dbConfig,
                'resource' => $resourceConfig
            ]);
        $this->flagManagerMock->expects($this->never())
            ->method('set');

        $this->step->execute();
    }

    /**
     * Case with incorrect slave connections
     */
    public function testExecuteSetSlaveConnectionHadNoEffect()
    {
        $this->dbConfigMock->expects($this->once())
            ->method('get')
            ->willReturn(['connection' => ['default' => self::DEFAULT_CONNECTION]]);
        $this->loggerMock->expects($this->once())
            ->method('info')
            ->with('Updating env.php DB connection configuration.');
        $this->configReaderMock->expects($this->once())
            ->method('read')
            ->willReturn([
                'db' => ['connection' => ['default' => self::DEFAULT_CONNECTION]],
                'resource' => ['default_setup' => self::RESOURCE_DEFAULT_SETUP]
            ]);
        $this->resourceConfigMock->expects($this->once())
            ->method('get')
            ->willReturn(['default_setup' => self::RESOURCE_DEFAULT_SETUP]);
        $this->loggerMock->expects($this->never())
            ->method('warning');
        $this->stageConfigMock->expects($this->exactly(2))
            ->method('get')
            ->withConsecutive(
                [DeployInterface::VAR_MYSQL_USE_SLAVE_CONNECTION],
                [DeployInterface::VAR_DATABASE_CONFIGURATION]
            )
            ->willReturnOnConsecutiveCalls(true, []);
        $this->envConnectionDataMock->expects($this->once())
            ->method('getHost')
            ->willReturn('host');
        $this->dbConfigMock->expects($this->once())
            ->method('isCustomConnectionCompatibleForSlave')
            ->with([], 'default', $this->envConnectionDataMock)
            ->willReturn(true);
        $this->loggerMock->expects($this->once())
            ->method('notice')
            ->with(
                'Enabling of the variable MYSQL_USE_SLAVE_CONNECTION had no effect'
                . ' for default connection, because default slave connection is not configured on your environment.'
            );
        $this->configWriterMock->expects($this->once())
            ->method('create')
            ->with([
                'db' => ['connection' => ['default' => self::DEFAULT_CONNECTION]],
                'resource' => ['default_setup' => self::RESOURCE_DEFAULT_SETUP],
            ]);
        $this->flagManagerMock->expects($this->never())
            ->method('set');

        $this->step->execute();
    }

    /**
     * Case when database was split before but with custom split connections
     */
    public function testExecuteSplitWasEnabledWithCustomConfiguration()
    {
        $mageConfig = [
            'db' => [
                'connection' => [
                    'default' => self::DEFAULT_CONNECTION,
                    'indexer' => self::DEFAULT_CONNECTION,
                    'checkout' => [
                        'host' => 'custom_checkout.host',
                        'dbname' => 'checkout.dbname',
                        'password' => 'checkout.password',
                        'username' => 'checkout.username',
                    ],
                    'sales' => [
                        'host' => 'custom_sales.host',
                        'dbname' => 'sales.dbname',
                        'password' => 'sales.password',
                        'username' => 'sales.username',
                    ],
                ]
            ],
            'resource' => [
                'default_setup' => self::RESOURCE_DEFAULT_SETUP,
                'checkout' => self::RESOURCE_CHECKOUT,
                'sales' => self::RESOURCE_SALE,
            ],
        ];
        $this->dbConfigMock->expects($this->once())
            ->method('get')
            ->willReturn([
                'connection' => [
                    'default' => self::DEFAULT_CONNECTION,
                    'indexer' => self::DEFAULT_CONNECTION,
                    'checkout' => self::CHECKOUT_CONNECTION,
                    'sales' => self::SALES_CONNECTION,
                ]
            ]);
        $this->loggerMock->expects($this->once())
            ->method('info')
            ->with('Updating env.php DB connection configuration.');
        $this->configReaderMock->expects($this->once())
            ->method('read')
            ->willReturn($mageConfig);
        $this->stageConfigMock->expects($this->exactly(1))
            ->method('get')
            ->with(DeployInterface::VAR_MYSQL_USE_SLAVE_CONNECTION)
            ->willReturnOnConsecutiveCalls(false, []);
        $this->loggerMock->expects($this->once())
            ->method('warning')
            ->with('For split databases used custom connections: checkout, sales');
        $this->flagManagerMock->expects($this->once())
            ->method('set')
            ->with(FlagManager::FLAG_IGNORE_SPLIT_DB);

        $this->step->execute();
    }

    /**
     * Case when split and slave was enabled previous deploy but salve is disabled right now
     *
     * @throws \Magento\MagentoCloud\Step\StepException
     */
    public function testExecuteDisableSlaveConnectionsWhenSplitDbEnabled()
    {
        $connections = [
            'connection' => [
                'default' => self::DEFAULT_CONNECTION,
                'indexer' => self::DEFAULT_CONNECTION,
                'checkout' => self::CHECKOUT_CONNECTION,
                'sales' => self::SALES_CONNECTION,
            ],
            'slave_connection' => [
                'default' => self::SLAVE_DEFAULT_CONNECTION,
                'indexer' => self::SLAVE_DEFAULT_CONNECTION,
                'checkout' => self::SLAVE_CHECKOUT_CONNECTION,
                'sales' => self::SLAVE_SALE_CONNECTION,
            ],
        ];
        $this->dbConfigMock->expects($this->once())
            ->method('get')
            ->willReturn($connections);
        $mageConfig = [
            'db' => $connections,
            'resource' => [
                'default_setup' => self::RESOURCE_DEFAULT_SETUP,
                'checkout' => self::RESOURCE_CHECKOUT,
                'sales' => self::RESOURCE_SALE,
            ],
        ];
        $this->loggerMock->expects($this->once())
            ->method('info')
            ->with('Updating env.php DB connection configuration.');
        $this->configReaderMock->expects($this->once())
            ->method('read')
            ->willReturn($mageConfig);
        $this->stageConfigMock->expects($this->once())
            ->method('get')
            ->with(DeployInterface::VAR_MYSQL_USE_SLAVE_CONNECTION)
            ->willReturnOnConsecutiveCalls(false, []);
        $this->configWriterMock->expects($this->once())
            ->method('create')
            ->with([
                'db' => [
                    'connection' => [
                        'default' => self::DEFAULT_CONNECTION,
                        'indexer' => self::DEFAULT_CONNECTION,
                        'checkout' => self::CHECKOUT_CONNECTION,
                        'sales' => self::SALES_CONNECTION,
                    ]
                ],
                'resource' => [
                    'default_setup' => self::RESOURCE_DEFAULT_SETUP,
                    'checkout' => self::RESOURCE_CHECKOUT,
                    'sales' => self::RESOURCE_SALE,
                ],
            ]);
        $this->flagManagerMock->expects($this->never())
            ->method('set');

        $this->step->execute();
    }

    /**
     * Case when custom connections added in DATABASE_CONFIGURATION.
     * Tests that custom resources saves and split db resources are ignored if split db not configured
     */
    public function testExecuteSaveCustomConfiguration()
    {
        $this->dbConfigMock->expects($this->once())
            ->method('get')
            ->willReturn([
                'connection' => [
                    'default' => self::DEFAULT_CONNECTION,
                    'indexer' => self::DEFAULT_CONNECTION,
                    'custom' => self::CUSTOM_CONNECTION,
                    'custom2' => self::CUSTOM_CONNECTION,
                ],
            ]);
        $this->resourceConfigMock->expects($this->once())
            ->method('get')
            ->willReturn([
                'custom' => self::RESOURCE_CUSTOM,
                'custom2' => self::RESOURCE_CUSTOM,
                ResourceConfig::RESOURCE_SALES => self::RESOURCE_SALE,
                ResourceConfig::RESOURCE_CHECKOUT => self::RESOURCE_CHECKOUT
            ]);
        $this->loggerMock->expects($this->once())
            ->method('info')
            ->with('Updating env.php DB connection configuration.');
        $this->configReaderMock->expects($this->once())
            ->method('read')
            ->willReturn([]);
        $this->stageConfigMock->expects($this->exactly(1))
            ->method('get')
            ->with(DeployInterface::VAR_MYSQL_USE_SLAVE_CONNECTION)
            ->willReturn(false);
        $this->configWriterMock->expects($this->once())
            ->method('create')
            ->with([
                'db' => [
                    'connection' => [
                        'default' => self::DEFAULT_CONNECTION,
                        'indexer' => self::DEFAULT_CONNECTION,
                        'custom' => self::CUSTOM_CONNECTION,
                        'custom2' => self::CUSTOM_CONNECTION,
                    ]
                ],
                'resource' => [
                    'custom' => self::RESOURCE_CUSTOM,
                    'custom2' => self::RESOURCE_CUSTOM,
                ]
            ]);

        $this->step->execute();
    }
}
