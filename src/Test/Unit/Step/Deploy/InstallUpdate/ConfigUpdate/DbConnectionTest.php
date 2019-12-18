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
use PHPUnit\Framework\MockObject\Matcher\InvokedCount;
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
    private $connectionDataFactoryMock;

    /**
     * @var ConnectionInterface|MockObject
     */
    private $connectionDataMock;

    /**
     * @var DbConnection
     */
    private $step;

    /**
     * @var FlagManager|MockObject
     */
    private $flagManagerMock;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        $this->stageConfigMock = $this->getMockForAbstractClass(DeployInterface::class);
        $this->dbConfigMock = $this->createMock(DbConfig::class);
        $this->resourceConfigMock = $this->createMock(ResourceConfig::class);
        $this->configWriterMock = $this->createMock(ConfigWriter::class);
        $this->configReaderMock = $this->createMock(ConfigReader::class);
        $this->connectionDataFactoryMock = $this->createMock(RelationshipConnectionFactory::class);
        $this->loggerMock = $this->getMockForAbstractClass(LoggerInterface::class);
        $this->flagManagerMock = $this->createMock(FlagManager::class);

        $this->connectionDataMock = $this->getMockForAbstractClass(ConnectionInterface::class);
        $this->connectionDataFactoryMock->expects($this->any())
            ->method('create')
            ->willReturn($this->connectionDataMock);

        $this->step = new DbConnection(
            $this->stageConfigMock,
            $this->dbConfigMock,
            $this->resourceConfigMock,
            $this->configWriterMock,
            $this->configReaderMock,
            new ConfigMerger(),
            $this->connectionDataFactoryMock,
            $this->loggerMock,
            $this->flagManagerMock
        );
    }

    /**
     * Case when an environment has no database configuration
     */
    public function testExecuteWithoutDbConnectionInEnvironment()
    {
        $this->dbConfigMock->expects($this->once())
            ->method('get')
            ->willReturn([]);
        $this->loggerMock->expects($this->once())
            ->method('notice')
            ->with('Database relationship configuration doesn\'t exist'
                . ' and database is not configured through .magento.env.yaml or env variable.'
                . ' Will be applied the previous database configuration.');
        $this->configWriterMock->expects($this->never())
            ->method('create');
        $this->step->execute();
    }

    /**
     * Case when slave connections and split database are not used
     */
    public function testExecute()
    {
        $defaultConnection = [
            'host' => 'host',
            'dbname' => 'dbname',
            'password' => 'password',
            'username' => 'username',
        ];
        $resourceConfig = [
            'default_setup' => ['connection' => 'default'],
        ];

        $dbConfig = [
            'connection' => [
                'default' => $defaultConnection,
                'indexer' => $defaultConnection,
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
        $this->stageConfigMock->expects($this->any())
            ->method('get')
            ->willReturnMap([
                [DeployInterface::VAR_MYSQL_USE_SLAVE_CONNECTION, false],
                [DeployInterface::VAR_DATABASE_CONFIGURATION, []],
            ]);
        $this->loggerMock->expects($this->never())
            ->method('warning');
        $this->configWriterMock->expects($this->once())
            ->method('create')
            ->with([
                'db' => $dbConfig,
                'resource' => $resourceConfig
            ]);

        $this->step->execute();
    }

    /**
     * Case when with not compatible database settings for slave connection
     */
    public function testExecuteWithNotCompatibleDatabaseSettingsForSlaveConnection()
    {
        $resourceConfig = [
            'default_setup' => ['connection' => 'default'],
        ];

        $dbConfig = [
            'connection' => [
                'default' => [
                    'host' => 'host',
                    'dbname' => 'dbname',
                    'password' => 'password',
                    'username' => 'username',
                ],
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
        $this->stageConfigMock->expects($this->any())
            ->method('get')
            ->willReturnMap([
                [DeployInterface::VAR_MYSQL_USE_SLAVE_CONNECTION, true],
                [DeployInterface::VAR_DATABASE_CONFIGURATION, []],
            ]);
        $this->connectionDataMock->expects($this->once())
            ->method('getHost')
            ->willReturn('host');
        $this->dbConfigMock->expects($this->once())
            ->method('isDbConfigCompatibleWithSlaveConnection')
            ->with([], 'default', $this->connectionDataMock)
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

        $this->step->execute();
    }

    /**
     * Case with slave connections
     */
    public function testExecuteSetSlaveConnection()
    {
        $this->dbConfigMock->expects($this->once())
            ->method('get')
            ->willReturn([
                'connection' => ['default' => ['host' => 'some.host']],
                'slave_connection' => ['default' => ['host' => 'some_slave.host']],
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
                'db' => ['connection' => ['default' => ['host' => 'some.host']]],
                'resource' => ['default_setup' => ['connection' => 'default']]
            ]);
        $this->loggerMock->expects($this->never())
            ->method('warning');
        $this->stageConfigMock->expects($this->any())
            ->method('get')
            ->willReturnMap([
                [DeployInterface::VAR_MYSQL_USE_SLAVE_CONNECTION, true],
                [DeployInterface::VAR_DATABASE_CONFIGURATION, []],
            ]);
        $this->connectionDataMock->expects($this->once())
            ->method('getHost')
            ->willReturn('some.host');
        $this->dbConfigMock->expects($this->once())
            ->method('isDbConfigCompatibleWithSlaveConnection')
            ->with([], 'default', $this->connectionDataMock)
            ->willReturn(true);

        $this->step->execute();
    }

    /**
     * Case with incorrect slave connections
     */
    public function testExecuteSetSlaveConnectionHadNoEffect()
    {
        $this->dbConfigMock->expects($this->once())
            ->method('get')
            ->willReturn(['connection' => ['default' => ['host' => 'some.host']]]);
        $this->loggerMock->expects($this->exactly(2))
            ->method('info')
            ->withConsecutive(
                ['Updating env.php DB connection configuration.'],
                [
                    'Enabling of the variable MYSQL_USE_SLAVE_CONNECTION had no effect because ' .
                    'slave connection is not configured on your environment.'
                ]
            );
        $this->configReaderMock->expects($this->once())
            ->method('read')
            ->willReturn([
                'db' => ['connection' => ['default' => ['host' => 'some.host']]],
                'resource' => ['default_setup' => ['connection' => 'default']]
            ]);
        $this->resourceConfigMock->expects($this->once())
            ->method('get')
            ->willReturn(['default_setup' => ['connection' => 'default']]);
        $this->loggerMock->expects($this->never())
            ->method('warning');
        $this->stageConfigMock->expects($this->any())
            ->method('get')
            ->willReturnMap([
                [DeployInterface::VAR_MYSQL_USE_SLAVE_CONNECTION, true],
                [DeployInterface::VAR_DATABASE_CONFIGURATION, []],
            ]);
        $this->connectionDataMock->expects($this->once())
            ->method('getHost')
            ->willReturn('some.host');
        $this->dbConfigMock->expects($this->once())
            ->method('isDbConfigCompatibleWithSlaveConnection')
            ->with([], 'default', $this->connectionDataMock)
            ->willReturn(true);

        $this->configWriterMock->expects($this->once())
            ->method('create')
            ->with([
                'db' => ['connection' => ['default' => ['host' => 'some.host']]],
                'resource' => ['default_setup' => ['connection' => 'default']],
            ]);

        $this->step->execute();
    }

    /**
     * Case when database was split before but with custom split connections
     */
    public function testExecuteSplitDbWasEnabledWithCustomConfiguration()
    {
        $defaultConnection = [
            'host' => 'host',
            'dbname' => 'dbname',
            'password' => 'password',
            'username' => 'username',
        ];
        $this->dbConfigMock->expects($this->once())
            ->method('get')
            ->willReturn([
                'connection' => [
                    'default' => $defaultConnection,
                    'indexer' => $defaultConnection,
                    'checkout' => [
                        'host' => 'checkout.host',
                        'dbname' => 'checkout.dbname',
                        'password' => 'checkout.password',
                        'username' => 'checkout.username',
                    ],
                    'sale' => [
                        'host' => 'sale.host',
                        'dbname' => 'sale.dbname',
                        'password' => 'sale.password',
                        'username' => 'sale.username',
                    ],
                ]
            ]);
        $this->loggerMock->expects($this->once())
            ->method('info')
            ->with('Updating env.php DB connection configuration.');
        $this->configReaderMock->expects($this->once())
            ->method('read')
            ->willReturn([
                'db' => [
                    'connection' => [
                        'default' => $defaultConnection,
                        'indexer' => $defaultConnection,
                        'checkout' => [
                            'host' => 'custom_checkout.host',
                            'dbname' => 'checkout.dbname',
                            'password' => 'checkout.password',
                            'username' => 'checkout.username',
                        ],
                        'sale' => [
                            'host' => 'custom_sale.host',
                            'dbname' => 'sale.dbname',
                            'password' => 'sale.password',
                            'username' => 'sale.username',
                        ],
                    ]
                ],
                'resource' => [
                    'default_setup' => ['connection' => 'default'],
                    'checkout' => ['connection' => 'checkout'],
                    'sale' => ['connection' => 'sale'],
                ],
            ]);
        $this->configWriterMock->expects($this->never())
            ->method('create');
        $this->loggerMock->expects($this->once())
            ->method('warning')
            ->with('For split databases used custom connections: checkout, sale');
        $this->flagManagerMock->expects($this->once())
            ->method('set')
            ->with(FlagManager::FLAG_IGNORE_SPLIT_DB);
        $this->step->execute();
    }

    /**
     * Case when database was split before but with custom default connections
     */
    public function testExecuteSplitDbWasEnabledWithDifferentMainConnection()
    {
        $defaultConnection = [
            'host' => 'host',
            'dbname' => 'dbname',
            'password' => 'password',
            'username' => 'username',
        ];
        $customDefaultConnection = [
            'host' => 'custom.host',
            'dbname' => 'custom.dbname',
            'password' => 'custom.password',
            'username' => 'custom.username',
        ];
        $this->dbConfigMock->expects($this->once())
            ->method('get')
            ->willReturn([
                'connection' => [
                    'default' => $defaultConnection,
                    'indexer' => $defaultConnection,
                    'checkout' => [
                        'host' => 'checkout.host',
                        'dbname' => 'checkout.dbname',
                        'password' => 'checkout.password',
                        'username' => 'checkout.username',
                    ],
                    'sale' => [
                        'host' => 'sale.host',
                        'dbname' => 'sale.dbname',
                        'password' => 'sale.password',
                        'username' => 'sale.username',
                    ],
                ]
            ]);
        $this->loggerMock->expects($this->once())
            ->method('info')
            ->with('Updating env.php DB connection configuration.');
        $this->configReaderMock->expects($this->once())
            ->method('read')
            ->willReturn([
                'db' => [
                    'connection' => [
                        'default' => $customDefaultConnection,
                        'indexer' => $customDefaultConnection,
                        'checkout' => [
                            'host' => 'custom_checkout.host',
                            'dbname' => 'checkout.dbname',
                            'password' => 'checkout.password',
                            'username' => 'checkout.username',
                        ],
                        'sale' => [
                            'host' => 'custom_sale.host',
                            'dbname' => 'sale.dbname',
                            'password' => 'sale.password',
                            'username' => 'sale.username',
                        ],
                    ]
                ],
                'resource' => [
                    'default_setup' => ['connection' => 'default'],
                    'checkout' => ['connection' => 'checkout'],
                    'sale' => ['connection' => 'sale'],
                ],
            ]);
        $this->loggerMock->expects($this->once())
            ->method('notice')
            ->with('Database was already split but deploy was configured with new connection.'
                . ' The previous connection data will be ignored.');
        $this->resourceConfigMock->expects($this->once())
            ->method('get')
            ->willReturn([
                'default_setup' => ['connection' => 'default'],
                'checkout' => ['connection' => 'checkout'],
                'sale' => ['connection' => 'sale'],
            ]);
        $this->stageConfigMock->expects($this->any())
            ->method('get')
            ->willReturnMap([
                [DeployInterface::VAR_MYSQL_USE_SLAVE_CONNECTION, false],
                [DeployInterface::VAR_DATABASE_CONFIGURATION, []],
            ]);

        $this->configWriterMock->expects($this->once())
            ->method('create')
            ->with([
                'db' => [
                    'connection' => [
                        'default' => $defaultConnection,
                        'indexer' => $defaultConnection,
                    ]
                ],
                'resource' => [
                    'default_setup' => ['connection' => 'default'],
                ],
            ]);

        $this->step->execute();
    }

    /**
     * Cases when database was split
     * @param array $varSplitDb
     * @param InvokedCount $expectsWarning
     * @param string $missedSplitConnection
     * @dataProvider dataProviderExecuteSplitDbEnabled
     *
     * @throws \Magento\MagentoCloud\Step\StepException
     */
    public function testExecuteSplitDbEnabled(
        array $varSplitDb,
        InvokedCount $expectsWarning,
        string $missedSplitConnection
    ) {
        $defaultConnection = [
            'host' => 'host',
            'dbname' => 'dbname',
            'password' => 'password',
            'username' => 'username',
        ];
        $checkoutConnection = [
            'host' => 'checkout.host',
            'dbname' => 'checkout.dbname',
            'password' => 'checkout.password',
            'username' => 'checkout.username',
        ];
        $saleConnection = [
            'host' => 'sale.host',
            'dbname' => 'sale.dbname',
            'password' => 'sale.password',
            'username' => 'sale.username',
        ];

        $this->dbConfigMock->expects($this->once())
            ->method('get')
            ->willReturn([
                'connection' => [
                    'default' => $defaultConnection,
                    'indexer' => $defaultConnection,
                    'checkout' => $checkoutConnection,
                    'sale' => $saleConnection,
                ]
            ]);
        $this->loggerMock->expects($this->once())
            ->method('info')
            ->with('Updating env.php DB connection configuration.');
        $this->configReaderMock->expects($this->once())
            ->method('read')
            ->willReturn([
                'db' => [
                    'connection' => [
                        'default' => $defaultConnection,
                        'indexer' => $defaultConnection,
                        'checkout' => $checkoutConnection,
                        'sale' => $saleConnection,
                    ]
                ],
                'resource' => [
                    'default_setup' => ['connection' => 'default'],
                    'checkout' => ['connection' => 'checkout'],
                    'sale' => ['connection' => 'sale'],
                ],
            ]);
        $this->configWriterMock->expects($this->never())
            ->method('create');
        $this->stageConfigMock->expects($this->once())
            ->method('get')
            ->with(DeployInterface::VAR_SPLIT_DB)
            ->willReturn($varSplitDb);
        $this->loggerMock->expects($expectsWarning)
            ->method('warning')
            ->with('Db ' . $missedSplitConnection . ' was split before, but SPLIT_DB does not have this info');

        $this->step->execute();
    }

    public function dataProviderExecuteSplitDbEnabled(): array
    {
        return [
            [
                'varSplitDb' => ['quote', 'sales'],
                'expectsWarning' => $this->never(),
                'mossedSplitConnection' => '',
            ],
            [
                'varSplitDb' => ['quote',],
                'expectsWarning' => $this->once(),
                'mossedSplitConnection' => 'sales',
            ],
            [
                'varSplitDb' => ['sales',],
                'expectsWarning' => $this->once(),
                'mossedSplitConnection' => 'quote',
            ]
        ];
    }
}
