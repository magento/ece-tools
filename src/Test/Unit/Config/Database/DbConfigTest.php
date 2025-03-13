<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Test\Unit\Config\Database;

use Magento\MagentoCloud\Config\ConfigException;
use Magento\MagentoCloud\Config\ConfigMerger;
use Magento\MagentoCloud\Config\Database\DbConfig;
use Magento\MagentoCloud\Config\Stage\DeployInterface;
use Magento\MagentoCloud\DB\Data\ConnectionInterface;
use Magento\MagentoCloud\DB\Data\RelationshipConnectionFactory;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @inheritdoc
 */
class DbConfigTest extends TestCase
{
    /**
     * @var DeployInterface|MockObject
     */
    private $stageConfigMock;

    /**
     * @var RelationshipConnectionFactory|MockObject
     */
    private $envConnectionDataFactoryMock;

    /**
     * @var DbConfig
     */
    private $dbConfig;

    /**
     * @var ConnectionInterface|MockObject
     */
    private $envConnectionDataDefaultMock;

    /**
     * @var ConnectionInterface|MockObject
     */
    private $envConnectionDataCheckoutMock;

    /**
     * @var ConnectionInterface|MockObject
     */
    private $envConnectionDataSaleMock;

    /**
     * @var ConnectionInterface|MockObject
     */
    private $envConnectionDataDefaultSlaveMock;
    /**
     * @var ConnectionInterface|MockObject
     */
    private $envConnectionDataCheckoutSlaveMock;
    /**
     * @var ConnectionInterface|MockObject
     */
    private $envConnectionDataSaleSlaveMock;

    /**
     * @throws \ReflectionException
     */
    protected function setUp(): void
    {
        $this->stageConfigMock = $this->getMockForAbstractClass(DeployInterface::class);
        $this->envConnectionDataFactoryMock = $this->createMock(RelationshipConnectionFactory::class);

        $this->envConnectionDataDefaultMock = $this->getMockForAbstractClass(ConnectionInterface::class);
        $this->envConnectionDataCheckoutMock = $this->getMockForAbstractClass(ConnectionInterface::class);
        $this->envConnectionDataSaleMock = $this->getMockForAbstractClass(ConnectionInterface::class);

        $this->envConnectionDataDefaultSlaveMock = $this->getMockForAbstractClass(ConnectionInterface::class);
        $this->envConnectionDataCheckoutSlaveMock = $this->getMockForAbstractClass(ConnectionInterface::class);
        $this->envConnectionDataSaleSlaveMock = $this->getMockForAbstractClass(ConnectionInterface::class);

        $this->envConnectionDataFactoryMock->expects($this->any())
            ->method('create')
            ->willReturnMap([
                [RelationshipConnectionFactory::CONNECTION_MAIN, $this->envConnectionDataDefaultMock],
                [RelationshipConnectionFactory::CONNECTION_QUOTE_MAIN, $this->envConnectionDataCheckoutMock],
                [RelationshipConnectionFactory::CONNECTION_SALES_MAIN, $this->envConnectionDataSaleMock],
                [RelationshipConnectionFactory::CONNECTION_SLAVE, $this->envConnectionDataDefaultSlaveMock],
                [RelationshipConnectionFactory::CONNECTION_QUOTE_SLAVE, $this->envConnectionDataCheckoutSlaveMock],
                [RelationshipConnectionFactory::CONNECTION_SALES_SLAVE, $this->envConnectionDataSaleSlaveMock],
            ]);

        $this->dbConfig = new DbConfig(
            $this->stageConfigMock,
            new ConfigMerger(),
            $this->envConnectionDataFactoryMock
        );
    }

    /**
     * @param array $customConfig
     * @param string $connectionName
     * @param bool $expectedResult
     * @dataProvider isConnectionCompatibleDataProvider
     */
    public function testIsCustomConnectionCompatibleForSlave($customConfig, $connectionName, $expectedResult)
    {
        $this->envConnectionDataDefaultMock->expects($this->any())
            ->method('getHost')
            ->willReturn('host');
        $this->envConnectionDataDefaultMock->expects($this->any())
            ->method('getDbName')
            ->willReturn('dbname');

        $this->assertEquals(
            $expectedResult,
            $this->dbConfig->isCustomConnectionCompatibleForSlave(
                $customConfig,
                $connectionName,
                $this->envConnectionDataDefaultMock
            )
        );
    }

    /**
     * @return array
     */
    public function isConnectionCompatibleDataProvider()
    {
        $config = [
            DbConfig::KEY_CONNECTION => [
                'connection1' => ['host' => 'host', 'dbname' => 'dbname'],
                'connection2' => ['host' => 'wrong host', 'dbname' => 'dbname'],
                'connection3' => ['host' => 'host', 'dbname' => 'wrong dbname'],
                'connection4' => ['host' => 'wrong host'],
                'connection5' => ['dbname' => 'wrong dbname'],
            ]
        ];
        return [
            [$config, 'connection1', true],
            [$config, 'connection2', false],
            [$config, 'connection3', false],
            [$config, 'connection4', false],
            [$config, 'connection5', false],
        ];
    }

    /**
     * @param array $envConnectionsData
     * @param array $customDbConfig
     * @param array $expectedConfig
     * @dataProvider getDataProvider
     * @throws ConfigException
     */
    public function testGet(
        array $envConnectionsData,
        array $customDbConfig,
        array $expectedConfig
    ) {
        $this->setEnvConnectionData($envConnectionsData);
        $this->stageConfigMock->expects($this->once())
            ->method('get')
            ->with(DeployInterface::VAR_DATABASE_CONFIGURATION)
            ->willReturn($customDbConfig);

        $this->assertEquals($expectedConfig, $this->dbConfig->get());
    }

    /**
     * @param array $envConnectionsData
     * @return void
     */
    private function setEnvConnectionData(array $envConnectionsData)
    {
        foreach ($this->getEnvConnectionMap() as $connectionKey => $connectionDataMock) {
            $connectionDataMock->expects($this->any())
                ->method('getHost')
                ->willReturn($envConnectionsData[$connectionKey]['host'] ?? '');
            $connectionDataMock->expects($this->any())
                ->method('getPort')
                ->willReturn($envConnectionsData[$connectionKey]['port'] ?? '');
            $connectionDataMock->expects($this->any())
                ->method('getDbName')
                ->willReturn($envConnectionsData[$connectionKey]['dbname'] ?? '');
            $connectionDataMock->expects($this->any())
                ->method('getUser')
                ->willReturn($envConnectionsData[$connectionKey]['username'] ?? '');
            $connectionDataMock->expects($this->any())
                ->method('getPassword')
                ->willReturn($envConnectionsData[$connectionKey]['password'] ?? '');
        }
    }

    /**
     * @return ConnectionInterface[]|MockObject[]
     */
    private function getEnvConnectionMap(): array
    {
        return [
            RelationshipConnectionFactory::CONNECTION_MAIN => $this->envConnectionDataDefaultMock,
            RelationshipConnectionFactory::CONNECTION_QUOTE_MAIN => $this->envConnectionDataCheckoutMock,
            RelationshipConnectionFactory::CONNECTION_SALES_MAIN => $this->envConnectionDataSaleMock,
            RelationshipConnectionFactory::CONNECTION_SLAVE => $this->envConnectionDataDefaultSlaveMock,
            RelationshipConnectionFactory::CONNECTION_QUOTE_SLAVE => $this->envConnectionDataCheckoutSlaveMock,
            RelationshipConnectionFactory::CONNECTION_SALES_SLAVE => $this->envConnectionDataSaleSlaveMock,
        ];
    }

    /**
     * @param array $relationshipConnections
     * @return array
     */
    private function getEnvConnectionsData(array $relationshipConnections): array
    {
        return array_intersect_key(
            [
                RelationshipConnectionFactorY::CONNECTION_MAIN => [
                    'host' => 'some_host',
                    'port' => '3306',
                    'dbname' => 'some_dbname',
                    'username' => 'some_username',
                    'password' => 'some_password',
                ],
                RelationshipConnectionFactory::CONNECTION_QUOTE_MAIN => [
                    'host' => 'some_host_quote',
                    'port' => '3307',
                    'dbname' => 'some_dbname_quote',
                    'username' => 'some_username_quote',
                    'password' => 'some_password_quote',
                ],
                RelationshipConnectionFactory::CONNECTION_SALES_MAIN => [
                    'host' => 'some_host_sales',
                    'port' => '3308',
                    'dbname' => 'some_dbname_sales',
                    'username' => 'some_username_sales',
                    'password' => 'some_password_sales',
                ],
                RelationshipConnectionFactory::CONNECTION_SLAVE => [
                    'host' => 'some_host_slave',
                    'port' => '3309',
                    'dbname' => 'some_dbname_slave',
                    'username' => 'some_username_slave',
                    'password' => 'some_password_slave',
                ],
                RelationshipConnectionFactory::CONNECTION_QUOTE_SLAVE => [
                    'host' => 'some_host_quote_slave',
                    'port' => '3310',
                    'dbname' => 'some_dbname_quote_slave',
                    'username' => 'some_username_quote_slave',
                    'password' => 'some_password_quote_slave',
                ],
                RelationshipConnectionFactory::CONNECTION_SALES_SLAVE => [
                    'host' => 'some_host_sales_slave',
                    'port' => '3311',
                    'dbname' => 'some_dbname_sales_slave',
                    'username' => 'some_username_sales_slave',
                    'password' => 'some_password_sales_slave',
                ]
            ],
            array_flip($relationshipConnections)
        );
    }

    /**
     * Data provider for testExecute.
     *
     * Return data for 5 parameters:
     * envConnectionsData - connections data from environment variable $MAGENTO_CLOUD_RELATIONSHIP
     * customDbConfig - custom db configuration from DATABASE_CONFIGURATION variable of .magento.env.yaml file
     * expectedConfig - result of updated config data for configuration file
     *
     * @return array
     *
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function getDataProvider()
    {
        $connection = [
            'username' => 'some_username',
            'host' => 'some_host',
            'dbname' => 'some_dbname',
            'password' => 'some_password',
        ];

        return [
            'default connection without slave' => [
                'envConnectionsData' => $this->getEnvConnectionsData([
                    RelationshipConnectionFactory::CONNECTION_MAIN,
                ]),
                'customDbConfig' => [],
                'expectedConfig' => [
                    'connection' => [
                        'default' => $connection,
                        'indexer' => $connection,
                    ],
                ],
            ],
            'default connection with slave' => [
                'envConnectionsData' => $this->getEnvConnectionsData([
                    RelationshipConnectionFactory::CONNECTION_MAIN,
                    RelationshipConnectionFactory::CONNECTION_SLAVE,
                ]),
                'customDbConfig' => [],
                'expectedConfig' => [
                    'connection' => [
                        'default' => $connection,
                        'indexer' => $connection,
                    ],
                    'slave_connection' => [
                        'default' => [
                            'username' => 'some_username_slave',
                            'host' => 'some_host_slave:3309',
                            'dbname' => 'some_dbname_slave',
                            'password' => 'some_password_slave',
                            'model' => 'mysql4',
                            'engine' => 'innodb',
                            'initStatements' => 'SET NAMES utf8;',
                            'active' => '1',
                            'synchronous_replication' => true,
                        ],
                    ],
                ],
            ],
            'custom environment db configuration only merge option' => [
                'envConnectionsData' => $this->getEnvConnectionsData([
                    RelationshipConnectionFactory::CONNECTION_MAIN,
                    RelationshipConnectionFactory::CONNECTION_SLAVE,
                ]),
                'customDbConfig' => ['_merge' => true],
                'expectedConfig' => [
                    'connection' => [
                        'default' => $connection,
                        'indexer' => $connection,
                    ],
                    'slave_connection' => [
                        'default' => [
                            'username' => 'some_username_slave',
                            'host' => 'some_host_slave:3309',
                            'dbname' => 'some_dbname_slave',
                            'password' => 'some_password_slave',
                            'model' => 'mysql4',
                            'engine' => 'innodb',
                            'initStatements' => 'SET NAMES utf8;',
                            'active' => '1',
                            'synchronous_replication' => true,
                        ],
                    ],
                ],
            ],
            'custom environment db configuration without merge' => [
                'envConnectionsData' => $this->getEnvConnectionsData([
                    RelationshipConnectionFactory::CONNECTION_MAIN,
                    RelationshipConnectionFactory::CONNECTION_SLAVE,
                ]),
                'customDbConfig' => [
                    'connection' => [
                        'default' => [
                            'host' => 'custom_host',
                            'dbname' => 'custom_dbname',
                            'driver_options' => [\PDO::MYSQL_ATTR_LOCAL_INFILE => 1],
                        ],
                    ],
                    'slave_connection' => [
                        'default' => [
                            'username' => 'custom_username_slave',
                            'host' => 'custom_host_slave:3399',
                            'dbname' => 'custom_dbname_slave',
                            'password' => 'custom_password_slave',
                        ],
                    ],
                ],
                'expectedConfig' => [
                    'connection' => [
                        'default' => [
                            'host' => 'custom_host',
                            'dbname' => 'custom_dbname',
                            'driver_options' => [\PDO::MYSQL_ATTR_LOCAL_INFILE => 1],
                        ],
                    ],
                    'slave_connection' => [
                        'default' => [
                            'username' => 'custom_username_slave',
                            'host' => 'custom_host_slave:3399',
                            'dbname' => 'custom_dbname_slave',
                            'password' => 'custom_password_slave',
                        ],
                    ],
                ],
            ],
            'custom environment db configuration with merge and without slave' => [
                'envConnectionsData' => $this->getEnvConnectionsData([
                    RelationshipConnectionFactory::CONNECTION_MAIN,
                ]),
                'customDbConfig' => [
                    'connection' => [
                        'default' => [
                            'host' => 'custom_host',
                            'dbname' => 'custom_dbname',
                            'driver_options' => [\PDO::MYSQL_ATTR_LOCAL_INFILE => 1],
                        ],
                        'indexer' => [
                            'driver_options' => [\PDO::MYSQL_ATTR_LOCAL_INFILE => 1],
                        ],
                    ],
                    '_merge' => true,
                ],
                'expectedConfig' => [
                    'connection' => [
                        'default' => [
                            'username' => 'some_username',
                            'host' => 'custom_host',
                            'dbname' => 'custom_dbname',
                            'password' => 'some_password',
                            'driver_options' => [\PDO::MYSQL_ATTR_LOCAL_INFILE => 1],
                        ],
                        'indexer' => [
                            'username' => 'some_username',
                            'host' => 'some_host',
                            'dbname' => 'some_dbname',
                            'password' => 'some_password',
                            'driver_options' => [\PDO::MYSQL_ATTR_LOCAL_INFILE => 1],
                        ],
                    ],
                ],
            ],
            'custom environment db configuration with merge set to false and without slave' => [
                'envConnectionsData' => $this->getEnvConnectionsData([
                    RelationshipConnectionFactory::CONNECTION_MAIN,
                ]),
                'customDbConfig' => [
                    'connection' => [
                        'default' => [
                            'host' => 'custom_host',
                            'dbname' => 'custom_dbname',
                            'driver_options' => [\PDO::MYSQL_ATTR_LOCAL_INFILE => 1],
                        ],
                        'indexer' => [
                            'driver_options' => [\PDO::MYSQL_ATTR_LOCAL_INFILE => 1],
                        ],
                    ],
                    '_merge' => false,
                ],
                'expectedConfig' => [
                    'connection' => [
                        'default' => [
                            'host' => 'custom_host',
                            'dbname' => 'custom_dbname',
                            'driver_options' => [\PDO::MYSQL_ATTR_LOCAL_INFILE => 1],
                        ],
                        'indexer' => [
                            'driver_options' => [\PDO::MYSQL_ATTR_LOCAL_INFILE => 1],
                        ],
                    ],
                ],
            ],
            'custom environment db configuration with merge and with slave' => [
                'envConnectionsData' => $this->getEnvConnectionsData([
                    RelationshipConnectionFactory::CONNECTION_MAIN,
                    RelationshipConnectionFactory::CONNECTION_SLAVE,
                ]),
                'customDbConfig' => [
                    'connection' => [
                        'default' => [
                            'driver_options' => [\PDO::MYSQL_ATTR_LOCAL_INFILE => 1],
                        ],
                        'indexer' => [
                            'driver_options' => [\PDO::MYSQL_ATTR_LOCAL_INFILE => 1],
                        ],
                    ],
                    '_merge' => true,
                ],
                'expectedConfig' => [
                    'connection' => [
                        'default' => [
                            'username' => 'some_username',
                            'host' => 'some_host',
                            'dbname' => 'some_dbname',
                            'password' => 'some_password',
                            'driver_options' => [\PDO::MYSQL_ATTR_LOCAL_INFILE => 1],
                        ],
                        'indexer' => [
                            'username' => 'some_username',
                            'host' => 'some_host',
                            'dbname' => 'some_dbname',
                            'password' => 'some_password',
                            'driver_options' => [\PDO::MYSQL_ATTR_LOCAL_INFILE => 1],
                        ],
                    ],
                    'slave_connection' => [
                        'default' => [
                            'username' => 'some_username_slave',
                            'host' => 'some_host_slave:3309',
                            'dbname' => 'some_dbname_slave',
                            'password' => 'some_password_slave',
                            'model' => 'mysql4',
                            'engine' => 'innodb',
                            'initStatements' => 'SET NAMES utf8;',
                            'active' => '1',
                            'synchronous_replication' => true,
                        ],
                    ],
                ],
            ],
            'custom environment db configuration with merge, with slave, and host changed' => [
                'envConnectionsData' => $this->getEnvConnectionsData([
                    RelationshipConnectionFactory::CONNECTION_MAIN,
                    RelationshipConnectionFactory::CONNECTION_SLAVE,
                ]),
                'customDbConfig' => [
                    'connection' => [
                        'default' => [
                            'host' => 'custom_host',
                            'driver_options' => [\PDO::MYSQL_ATTR_LOCAL_INFILE => 1],
                        ],
                        'indexer' => [
                            'driver_options' => [\PDO::MYSQL_ATTR_LOCAL_INFILE => 1],
                        ],
                    ],
                    '_merge' => true,
                ],
                'expectedConfig' => [
                    'connection' => [
                        'default' => [
                            'username' => 'some_username',
                            'host' => 'custom_host',
                            'dbname' => 'some_dbname',
                            'password' => 'some_password',
                            'driver_options' => [\PDO::MYSQL_ATTR_LOCAL_INFILE => 1],
                        ],
                        'indexer' => [
                            'username' => 'some_username',
                            'host' => 'some_host',
                            'dbname' => 'some_dbname',
                            'password' => 'some_password',
                            'driver_options' => [\PDO::MYSQL_ATTR_LOCAL_INFILE => 1],
                        ],
                    ],
                ],
            ],
            'custom environment db configuration with custom slave connection and without merge' => [
                'envConnectionsData' => $this->getEnvConnectionsData([
                    RelationshipConnectionFactory::CONNECTION_MAIN,
                    RelationshipConnectionFactory::CONNECTION_SLAVE,
                ]),
                'customDbConfig' => [
                    'connection' => [
                        'default' => [
                            'host' => 'custom_host',
                            'dbname' => 'custom_dbname',
                            'driver_options' => [\PDO::MYSQL_ATTR_LOCAL_INFILE => 1],
                        ],
                    ],
                    'slave_connection' => [
                        'default' => [
                            'host' => 'custom_slave_host:3388',
                            'username' => 'custom_slave_user',
                            'dbname' => 'custom_slave_name',
                            'password' => 'custom_slave_password',
                        ],
                    ],
                ],
                'expectedConfig' => [
                    'connection' => [
                        'default' => [
                            'host' => 'custom_host',
                            'dbname' => 'custom_dbname',
                            'driver_options' => [\PDO::MYSQL_ATTR_LOCAL_INFILE => 1],
                        ],
                    ],
                    'slave_connection' => [
                        'default' => [
                            'host' => 'custom_slave_host:3388',
                            'username' => 'custom_slave_user',
                            'dbname' => 'custom_slave_name',
                            'password' => 'custom_slave_password',
                        ],
                    ],
                ],
            ],
            'environment db configuration with custom slave connection and with merge and use slave connection' => [
                'envConnectionsData' => $this->getEnvConnectionsData([
                    RelationshipConnectionFactory::CONNECTION_MAIN,
                    RelationshipConnectionFactory::CONNECTION_SLAVE,
                ]),
                'customDbConfig' => [
                    'slave_connection' => [
                        'default' => [
                            'host' => 'custom_slave_host:3377',
                            'username' => 'custom_slave_username',
                            'dbname' => 'custom_slave_dbname',
                            'password' => 'custom_slave.password',
                        ],
                    ],
                    '_merge' => true
                ],
                'expectedConfig' => [
                    'connection' => [
                        'default' => [
                            'username' => 'some_username',
                            'host' => 'some_host',
                            'dbname' => 'some_dbname',
                            'password' => 'some_password',
                        ],
                        'indexer' => [
                            'username' => 'some_username',
                            'host' => 'some_host',
                            'dbname' => 'some_dbname',
                            'password' => 'some_password',
                        ],
                    ],
                    'slave_connection' => [
                        'default' => [
                            'host' => 'custom_slave_host:3377',
                            'username' => 'custom_slave_username',
                            'dbname' => 'custom_slave_dbname',
                            'password' => 'custom_slave.password',
                            'model' => 'mysql4',
                            'engine' => 'innodb',
                            'initStatements' => 'SET NAMES utf8;',
                            'active' => '1',
                            'synchronous_replication' => true,
                        ],
                    ],
                ],
            ],
            'environment db config with custom slave connection and with merge and use without slave connection' => [
                'envConnectionsData' => $this->getEnvConnectionsData([
                    RelationshipConnectionFactory::CONNECTION_MAIN
                ]),
                'customDbConfig' => [
                    'slave_connection' => [
                        'default' => [
                            'host' => 'custom_slave_host:3398',
                            'username' => 'custom_slave_username',
                            'dbname' => 'custom_slave_dbname',
                            'password' => 'custom_slave_password',
                        ],
                    ],
                    '_merge' => true
                ],
                'expectedConfig' => [
                    'connection' => [
                        'default' => [
                            'username' => 'some_username',
                            'host' => 'some_host',
                            'dbname' => 'some_dbname',
                            'password' => 'some_password',
                        ],
                        'indexer' => [
                            'username' => 'some_username',
                            'host' => 'some_host',
                            'dbname' => 'some_dbname',
                            'password' => 'some_password',
                        ],
                    ],
                    'slave_connection' => [
                        'default' => [
                            'host' => 'custom_slave_host:3398',
                            'username' => 'custom_slave_username',
                            'dbname' => 'custom_slave_dbname',
                            'password' => 'custom_slave_password',
                        ],
                    ],
                ],
            ],
            'environment db config with split connections without slave and custom db configuration' => [
                'envConnectionsData' => $this->getEnvConnectionsData([
                    RelationshipConnectionFactory::CONNECTION_MAIN,
                    RelationshipConnectionFactory::CONNECTION_QUOTE_MAIN,
                    RelationshipConnectionFactory::CONNECTION_SALES_MAIN,
                ]),
                'customDbConfig' => [],
                'expectedConfig' => [
                    'connection' => [
                        'default' => [
                            'username' => 'some_username',
                            'host' => 'some_host',
                            'dbname' => 'some_dbname',
                            'password' => 'some_password',
                        ],
                        'indexer' => [
                            'username' => 'some_username',
                            'host' => 'some_host',
                            'dbname' => 'some_dbname',
                            'password' => 'some_password',
                        ],
                        'checkout' => [
                            'username' => 'some_username_quote',
                            'host' => 'some_host_quote:3307',
                            'dbname' => 'some_dbname_quote',
                            'password' => 'some_password_quote',
                            'model' => 'mysql4',
                            'engine' => 'innodb',
                            'initStatements' => 'SET NAMES utf8;',
                            'active' => '1',
                        ],
                        'sales' => [
                            'username' => 'some_username_sales',
                            'host' => 'some_host_sales:3308',
                            'dbname' => 'some_dbname_sales',
                            'password' => 'some_password_sales',
                            'model' => 'mysql4',
                            'engine' => 'innodb',
                            'initStatements' => 'SET NAMES utf8;',
                            'active' => '1',
                        ]
                    ],
                ],
            ],
            'environment db config with split and slave connections and without custom db configuration' => [
                'envConnectionsData' => $this->getEnvConnectionsData([
                    RelationshipConnectionFactory::CONNECTION_MAIN,
                    RelationshipConnectionFactory::CONNECTION_SLAVE,
                    RelationshipConnectionFactory::CONNECTION_QUOTE_MAIN,
                    RelationshipConnectionFactory::CONNECTION_QUOTE_SLAVE,
                    RelationshipConnectionFactory::CONNECTION_SALES_MAIN,
                    RelationshipConnectionFactory::CONNECTION_SALES_SLAVE,
                ]),
                'customDbConfig' => [],
                'expectedConfig' => [
                    'connection' => [
                        'default' => [
                            'username' => 'some_username',
                            'host' => 'some_host',
                            'dbname' => 'some_dbname',
                            'password' => 'some_password',
                        ],
                        'indexer' => [
                            'username' => 'some_username',
                            'host' => 'some_host',
                            'dbname' => 'some_dbname',
                            'password' => 'some_password',
                        ],
                        'checkout' => [
                            'username' => 'some_username_quote',
                            'host' => 'some_host_quote:3307',
                            'dbname' => 'some_dbname_quote',
                            'password' => 'some_password_quote',
                            'model' => 'mysql4',
                            'engine' => 'innodb',
                            'initStatements' => 'SET NAMES utf8;',
                            'active' => '1',
                        ],
                        'sales' => [
                            'username' => 'some_username_sales',
                            'host' => 'some_host_sales:3308',
                            'dbname' => 'some_dbname_sales',
                            'password' => 'some_password_sales',
                            'model' => 'mysql4',
                            'engine' => 'innodb',
                            'initStatements' => 'SET NAMES utf8;',
                            'active' => '1',
                        ],
                    ],
                    'slave_connection' => [
                        'default' => [
                            'host' => 'some_host_slave:3309',
                            'username' => 'some_username_slave',
                            'dbname' => 'some_dbname_slave',
                            'password' => 'some_password_slave',
                            'model' => 'mysql4',
                            'engine' => 'innodb',
                            'initStatements' => 'SET NAMES utf8;',
                            'active' => '1',
                            'synchronous_replication' => true,
                        ],
                        'checkout' => [
                            'host' => 'some_host_quote_slave:3310',
                            'username' => 'some_username_quote_slave',
                            'dbname' => 'some_dbname_quote_slave',
                            'password' => 'some_password_quote_slave',
                            'model' => 'mysql4',
                            'engine' => 'innodb',
                            'initStatements' => 'SET NAMES utf8;',
                            'active' => '1',
                            'synchronous_replication' => true,
                        ],
                        'sales' => [
                            'host' => 'some_host_sales_slave:3311',
                            'username' => 'some_username_sales_slave',
                            'dbname' => 'some_dbname_sales_slave',
                            'password' => 'some_password_sales_slave',
                            'model' => 'mysql4',
                            'engine' => 'innodb',
                            'initStatements' => 'SET NAMES utf8;',
                            'active' => '1',
                            'synchronous_replication' => true,
                        ],
                    ],
                ],
            ],
            'environment db config with split and slave connections and with custom split db config without merge' => [
                'envConnectionsData' => $this->getEnvConnectionsData([
                    RelationshipConnectionFactory::CONNECTION_MAIN,
                    RelationshipConnectionFactory::CONNECTION_SLAVE,
                    RelationshipConnectionFactory::CONNECTION_QUOTE_MAIN,
                    RelationshipConnectionFactory::CONNECTION_QUOTE_SLAVE,
                    RelationshipConnectionFactory::CONNECTION_SALES_MAIN,
                    RelationshipConnectionFactory::CONNECTION_SALES_SLAVE,
                ]),
                'customDbConfig' => [
                    '_merge' => false,
                    'connection' => [
                        'default' => [
                            'username' => 'custom_username',
                            'host' => 'custom_host',
                            'dbname' => 'custom_dbname',
                            'password' => 'custom_password',
                        ],
                        'indexer' => [
                            'username' => 'custom_other_username',
                            'host' => 'custom_other_host',
                            'dbname' => 'custom_other_dbname',
                            'password' => 'custom_other_password',
                        ],
                        'checkout' => [
                            'username' => 'custom_username_quote',
                            'host' => 'custom_host_quote:3344',
                            'dbname' => 'custom_dbname_quote',
                            'password' => 'custom_password_quote',
                        ],
                        'sales' => [
                            'username' => 'custom_username_sales',
                            'host' => 'custom_host_sales:3355',
                            'dbname' => 'custom_dbname_sales',
                            'password' => 'custom_password_sales',
                        ],
                    ],
                    'slave_connection' => [
                        'default' => [
                            'host' => 'custom_host_slave:3366',
                            'username' => 'custom_username_slave',
                            'dbname' => 'custom_dbname_slave',
                            'password' => 'custom_password_slave',
                        ],
                        'checkout' => [
                            'host' => 'custom_host_quote_slave:3377',
                            'username' => 'custom_username_quote_slave',
                            'dbname' => 'custom_dbname_quote_slave',
                            'password' => 'custom_password_quote_slave',
                        ],
                        'sales' => [
                            'host' => 'custom_host_sales_slave:3388',
                            'username' => 'custom_username_sales_slave',
                            'dbname' => 'custom_dbname_sales_slave',
                            'password' => 'custom_password_sales_slave',
                        ],
                    ],
                ],
                'expectedConfig' => [
                    'connection' => [
                        'default' => [
                            'username' => 'custom_username',
                            'host' => 'custom_host',
                            'dbname' => 'custom_dbname',
                            'password' => 'custom_password',
                        ],
                        'indexer' => [
                            'username' => 'custom_other_username',
                            'host' => 'custom_other_host',
                            'dbname' => 'custom_other_dbname',
                            'password' => 'custom_other_password',
                        ],

                    ],
                    'slave_connection' => [
                        'default' => [
                            'host' => 'custom_host_slave:3366',
                            'username' => 'custom_username_slave',
                            'dbname' => 'custom_dbname_slave',
                            'password' => 'custom_password_slave',
                        ],
                    ],

                ],
            ],
            'environment db config with split and slave connections and with custom split db config with merge' => [
                'envConnectionsData' => $this->getEnvConnectionsData([
                    RelationshipConnectionFactory::CONNECTION_MAIN,
                    RelationshipConnectionFactory::CONNECTION_SLAVE,
                    RelationshipConnectionFactory::CONNECTION_QUOTE_MAIN,
                    RelationshipConnectionFactory::CONNECTION_QUOTE_SLAVE,
                    RelationshipConnectionFactory::CONNECTION_SALES_MAIN,
                    RelationshipConnectionFactory::CONNECTION_SALES_SLAVE,
                ]),
                'customDbConfig' => [
                    '_merge' => true,
                    'connection' => [
                        'default' => [
                            'username' => 'custom_username',
                            'host' => 'custom_host',
                            'dbname' => 'custom_dbname',
                            'password' => 'custom_password',
                        ],
                        'indexer' => [
                            'username' => 'custom_other_username',
                            'host' => 'custom_other_host',
                            'dbname' => 'custom_other_dbname',
                            'password' => 'custom_other_password',
                        ],
                        'checkout' => [
                            'username' => 'custom_username_quote',
                            'host' => 'custom_host_quote:3344',
                            'dbname' => 'custom_dbname_quote',
                            'password' => 'custom_password_quote',
                        ],
                        'sales' => [
                            'username' => 'custom_username_sales',
                            'host' => 'custom_host_sales:3355',
                            'dbname' => 'custom_dbname_sales',
                            'password' => 'custom_password_sales',
                        ],
                    ],
                    'slave_connection' => [
                        'default' => [
                            'host' => 'custom_host_slave:3366',
                            'username' => 'custom_username_slave',
                            'dbname' => 'custom_dbname_slave',
                            'password' => 'custom_password_slave',
                        ],
                        'checkout' => [
                            'host' => 'custom_host_quote_slave:3377',
                            'username' => 'custom_username_quote_slave',
                            'dbname' => 'custom_dbname_quote_slave',
                            'password' => 'custom_password_quote_slave',
                        ],
                        'sales' => [
                            'host' => 'custom_host_sales_slave:3388',
                            'username' => 'custom_username_sales_slave',
                            'dbname' => 'custom_dbname_sales_slave',
                            'password' => 'custom_password_sales_slave',
                        ],
                    ],
                ],
                'expectedConfig' => [
                    'connection' => [
                        'default' => [
                            'username' => 'custom_username',
                            'host' => 'custom_host',
                            'dbname' => 'custom_dbname',
                            'password' => 'custom_password',
                        ],
                        'indexer' => [
                            'username' => 'custom_other_username',
                            'host' => 'custom_other_host',
                            'dbname' => 'custom_other_dbname',
                            'password' => 'custom_other_password',
                        ],
                        'checkout' => [
                            'host' => 'some_host_quote:3307',
                            'username' => 'some_username_quote',
                            'dbname' => 'some_dbname_quote',
                            'password' => 'some_password_quote',
                            'model' => 'mysql4',
                            'engine' => 'innodb',
                            'initStatements' => 'SET NAMES utf8;',
                            'active' => '1',
                        ],
                        'sales' => [
                            'host' => 'some_host_sales:3308',
                            'username' => 'some_username_sales',
                            'dbname' => 'some_dbname_sales',
                            'password' => 'some_password_sales',
                            'model' => 'mysql4',
                            'engine' => 'innodb',
                            'initStatements' => 'SET NAMES utf8;',
                            'active' => '1',
                        ],
                    ],
                    'slave_connection' => [
                        'default' => [
                            'host' => 'custom_host_slave:3366',
                            'username' => 'custom_username_slave',
                            'dbname' => 'custom_dbname_slave',
                            'password' => 'custom_password_slave',
                        ],
                        'checkout' => [
                            'host' => 'some_host_quote_slave:3310',
                            'username' => 'some_username_quote_slave',
                            'dbname' => 'some_dbname_quote_slave',
                            'password' => 'some_password_quote_slave',
                            'model' => 'mysql4',
                            'engine' => 'innodb',
                            'initStatements' => 'SET NAMES utf8;',
                            'active' => '1',
                            'synchronous_replication' => true,
                        ],
                        'sales' => ['host' => 'some_host_sales_slave:3311',
                            'username' => 'some_username_sales_slave',
                            'dbname' => 'some_dbname_sales_slave',
                            'password' => 'some_password_sales_slave',
                            'model' => 'mysql4',
                            'engine' => 'innodb',
                            'initStatements' => 'SET NAMES utf8;',
                            'active' => '1',
                            'synchronous_replication' => true,
                        ],
                    ],
                ],
            ]
        ];
    }
}
