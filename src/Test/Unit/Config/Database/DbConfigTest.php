<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Test\Unit\Config\Database;

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
    private $connectionFactoryMock;

    /**
     * @var DbConfig
     */
    private $dbConfig;

    /**
     * @var ConnectionInterface|MockObject
     */
    private $connectionDataDefaultMock;

    /**
     * @var ConnectionInterface|MockObject
     */
    private $connectionDataCheckoutMock;

    /**
     * @var ConnectionInterface|MockObject
     */
    private $connectionDataSaleMock;

    /**
     * @var ConnectionInterface|MockObject
     */
    private $connectionDataDefaultSlaveMock;
    /**
     * @var ConnectionInterface|MockObject
     */
    private $connectionDataCheckoutSlaveMock;
    /**
     * @var ConnectionInterface|MockObject
     */
    private $connectionDataSaleSlaveMock;

    /**
     * @throws \ReflectionException
     */
    protected function setUp()
    {
        $this->stageConfigMock = $this->getMockForAbstractClass(DeployInterface::class);
        $this->connectionFactoryMock = $this->createMock(RelationshipConnectionFactory::class);

        $this->connectionDataDefaultMock = $this->getMockForAbstractClass(ConnectionInterface::class);
        $this->connectionDataCheckoutMock = $this->getMockForAbstractClass(ConnectionInterface::class);
        $this->connectionDataSaleMock = $this->getMockForAbstractClass(ConnectionInterface::class);

        $this->connectionDataDefaultSlaveMock = $this->getMockForAbstractClass(ConnectionInterface::class);
        $this->connectionDataCheckoutSlaveMock = $this->getMockForAbstractClass(ConnectionInterface::class);
        $this->connectionDataSaleSlaveMock = $this->getMockForAbstractClass(ConnectionInterface::class);

        $this->connectionFactoryMock->expects($this->any())
            ->method('create')
            ->willReturnMap([
                [RelationshipConnectionFactory::CONNECTION_MAIN, $this->connectionDataDefaultMock],
                [RelationshipConnectionFactory::CONNECTION_QUOTE_MAIN, $this->connectionDataCheckoutMock],
                [RelationshipConnectionFactory::CONNECTION_SALES_MAIN, $this->connectionDataSaleMock],
                [RelationshipConnectionFactory::CONNECTION_SLAVE, $this->connectionDataDefaultSlaveMock],
                [RelationshipConnectionFactory::CONNECTION_QUOTE_SLAVE, $this->connectionDataCheckoutSlaveMock],
                [RelationshipConnectionFactory::CONNECTION_SALES_SLAVE, $this->connectionDataSaleSlaveMock],
            ]);

        $this->dbConfig = new DbConfig(
            $this->stageConfigMock,
            new ConfigMerger(),
            $this->connectionFactoryMock
        );
    }

    /**
     * @param array $connectionsData
     * @param array $envDbConfig
     * @param boolean $setSlave
     * @param array $expectedConfig
     * @dataProvider getDataProvider
     */
    public function testGet(
        array $connectionsData,
        array $envDbConfig,
        $setSlave,
        array $expectedConfig
    ) {
        $this->setConnectionData($connectionsData);
        $this->stageConfigMock->expects($this->any())
            ->method('get')
            ->willReturnMap([
                [DeployInterface::VAR_DATABASE_CONFIGURATION, $envDbConfig],
                [DeployInterface::VAR_MYSQL_USE_SLAVE_CONNECTION, $setSlave],
            ]);

        $this->assertEquals($expectedConfig, $this->dbConfig->get());
    }

    /**
     * @param array $connectionsData
     * @return void
     */
    private function setConnectionData(array $connectionsData)
    {
        foreach ($this->getRelationshipConnectionMap() as $connectionKey => $connectionDataMock) {
            $connectionDataMock->expects($this->any())
                ->method('getHost')
                ->willReturn($connectionsData[$connectionKey]['host'] ?? '');
            $connectionDataMock->expects($this->any())
                ->method('getPort')
                ->willReturn($connectionsData[$connectionKey]['port'] ?? '');
            $connectionDataMock->expects($this->any())
                ->method('getDbName')
                ->willReturn($connectionsData[$connectionKey]['dbname'] ?? '');
            $connectionDataMock->expects($this->any())
                ->method('getUser')
                ->willReturn($connectionsData[$connectionKey]['username'] ?? '');
            $connectionDataMock->expects($this->any())
                ->method('getPassword')
                ->willReturn($connectionsData[$connectionKey]['password'] ?? '');
        }
    }

    /**
     * @return ConnectionInterface[]|MockObject[]
     */
    private function getRelationshipConnectionMap(): array
    {
        return [
            RelationshipConnectionFactory::CONNECTION_MAIN => $this->connectionDataDefaultMock,
            RelationshipConnectionFactory::CONNECTION_QUOTE_MAIN => $this->connectionDataCheckoutMock,
            RelationshipConnectionFactory::CONNECTION_SALES_MAIN => $this->connectionDataSaleMock,
            RelationshipConnectionFactory::CONNECTION_SLAVE => $this->connectionDataDefaultSlaveMock,
            RelationshipConnectionFactory::CONNECTION_QUOTE_SLAVE => $this->connectionDataCheckoutSlaveMock,
            RelationshipConnectionFactory::CONNECTION_SALES_SLAVE => $this->connectionDataSaleSlaveMock,
        ];
    }

    /**
     * @param array $relationshipConnections
     * @return array
     */
    private function getConnectionsData(array $relationshipConnections): array
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
     * connectionsData - connections data from environment variable $MAGENTO_CLOUD_RELATIONSHIP
     * envDbConfig - custom db configuration from DATABASE_CONFIGURATION variable of .magento.env.yaml file
     * setSlave - value for MYSQL_USE_SLAVE_CONNECTION variable of .magento.env.yaml file
     * expectedConfig - result of updated config data for configuration file
     *
     * @return array
     *
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function getDataProvider()
    {
        return [
            'default connection without slave' => [
                'connectionsData' => $this->getConnectionsData([RelationshipConnectionFactory::CONNECTION_MAIN]),
                'envDbConfig' => [],
                'setSlave' => false,
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
                ],
            ],
            'default connection with slave' => [
                'connectionsData' => $this->getConnectionsData([
                    RelationshipConnectionFactory::CONNECTION_MAIN,
                    RelationshipConnectionFactory::CONNECTION_SLAVE,
                ]),
                'envDbConfig' => [],
                'setSlave' => true,
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
                            'username' => 'some_username_slave',
                            'host' => 'some_host_slave:3309',
                            'dbname' => 'some_dbname_slave',
                            'password' => 'some_password_slave',
                            'model' => 'mysql4',
                            'engine' => 'innodb',
                            'initStatements' => 'SET NAMES utf8;',
                            'active' => '1',
                        ],
                    ],
                ],
            ],
            'custom environment db configuration only merge option' => [
                'connectionsData' => $this->getConnectionsData([
                    RelationshipConnectionFactory::CONNECTION_MAIN,
                    RelationshipConnectionFactory::CONNECTION_SLAVE,
                ]),
                'envDbConfig' => ['_merge' => true],
                'setSlave' => true,
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
                            'username' => 'some_username_slave',
                            'host' => 'some_host_slave:3309',
                            'dbname' => 'some_dbname_slave',
                            'password' => 'some_password_slave',
                            'model' => 'mysql4',
                            'engine' => 'innodb',
                            'initStatements' => 'SET NAMES utf8;',
                            'active' => '1',
                        ],
                    ],
                ],
            ],
            'custom environment db configuration without merge' => [
                'connectionsData' => $this->getConnectionsData([
                    RelationshipConnectionFactory::CONNECTION_MAIN,
                    RelationshipConnectionFactory::CONNECTION_SLAVE,
                ]),
                'envDbConfig' => [
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
                'setSlave' => true,
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
                'connectionsData' => $this->getConnectionsData([
                    RelationshipConnectionFactory::CONNECTION_MAIN,
                    RelationshipConnectionFactory::CONNECTION_SLAVE,
                ]),
                'envDbConfig' => [
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
                'setSlave' => false,
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
                'connectionsData' => $this->getConnectionsData([
                    RelationshipConnectionFactory::CONNECTION_MAIN,
                    RelationshipConnectionFactory::CONNECTION_SLAVE,
                ]),
                'envDbConfig' => [
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
                'setSlave' => false,
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
                'connectionsData' => $this->getConnectionsData([
                    RelationshipConnectionFactory::CONNECTION_MAIN,
                    RelationshipConnectionFactory::CONNECTION_SLAVE,
                ]),
                'envDbConfig' => [
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
                'setSlave' => true,
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
                        ],
                    ],
                ],
            ],
            'custom environment db configuration with merge, with slave, and host changed' => [
                'connectionsData' => $this->getConnectionsData([
                    RelationshipConnectionFactory::CONNECTION_MAIN,
                    RelationshipConnectionFactory::CONNECTION_SLAVE,
                ]),
                'envDbConfig' => [
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
                'setSlave' => true,
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
                'connectionsData' => $this->getConnectionsData([
                    RelationshipConnectionFactory::CONNECTION_MAIN,
                    RelationshipConnectionFactory::CONNECTION_SLAVE,
                ]),
                'envDbConfig' => [
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
                'setSlave' => true,
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
                'connectionsData' => $this->getConnectionsData([
                    RelationshipConnectionFactory::CONNECTION_MAIN,
                    RelationshipConnectionFactory::CONNECTION_SLAVE,
                ]),
                'envDbConfig' => [
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
                'setSlave' => true,
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
                        ],
                    ],
                ],
            ],
            'environment db config with custom slave connection and with merge and use without slave connection' => [
                'connectionsData' => $this->getConnectionsData([RelationshipConnectionFactory::CONNECTION_MAIN]),
                'envDbConfig' => [
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
                'setSlave' => false,
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
                'connectionsData' => $this->getConnectionsData([
                    RelationshipConnectionFactory::CONNECTION_MAIN,
                    RelationshipConnectionFactory::CONNECTION_SLAVE,
                    RelationshipConnectionFactory::CONNECTION_QUOTE_MAIN,
                    RelationshipConnectionFactory::CONNECTION_QUOTE_SLAVE,
                    RelationshipConnectionFactory::CONNECTION_SALES_MAIN,
                    RelationshipConnectionFactory::CONNECTION_SALES_SLAVE,
                ]),
                'envDbConfig' => [],
                'setSlave' => false,
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
                        'sale' => [
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
                'connectionsData' => $this->getConnectionsData([
                    RelationshipConnectionFactory::CONNECTION_MAIN,
                    RelationshipConnectionFactory::CONNECTION_SLAVE,
                    RelationshipConnectionFactory::CONNECTION_QUOTE_MAIN,
                    RelationshipConnectionFactory::CONNECTION_QUOTE_SLAVE,
                    RelationshipConnectionFactory::CONNECTION_SALES_MAIN,
                    RelationshipConnectionFactory::CONNECTION_SALES_SLAVE,
                ]),
                'envDbConfig' => [],
                'setSlave' => true,
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
                        'sale' => [
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
                        ],
                        'sale' => [
                            'host' => 'some_host_sales_slave:3311',
                            'username' => 'some_username_sales_slave',
                            'dbname' => 'some_dbname_sales_slave',
                            'password' => 'some_password_sales_slave',
                            'model' => 'mysql4',
                            'engine' => 'innodb',
                            'initStatements' => 'SET NAMES utf8;',
                            'active' => '1',
                        ],
                    ],
                ],
            ],
            'environment db config with split and slave connections and with custom split db config without merge' => [
                'connectionsData' => $this->getConnectionsData([
                    RelationshipConnectionFactory::CONNECTION_MAIN,
                    RelationshipConnectionFactory::CONNECTION_SLAVE,
                    RelationshipConnectionFactory::CONNECTION_QUOTE_MAIN,
                    RelationshipConnectionFactory::CONNECTION_QUOTE_SLAVE,
                    RelationshipConnectionFactory::CONNECTION_SALES_MAIN,
                    RelationshipConnectionFactory::CONNECTION_SALES_SLAVE,
                ]),
                'envDbConfig' => [
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
                        'sale' => [
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
                        'sale' => [
                            'host' => 'custom_host_sales_slave:3388',
                            'username' => 'custom_username_sales_slave',
                            'dbname' => 'custom_dbname_sales_slave',
                            'password' => 'custom_password_sales_slave',
                        ],
                    ],
                ],
                'setSlave' => true,
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
                'connectionsData' => $this->getConnectionsData([
                    RelationshipConnectionFactory::CONNECTION_MAIN,
                    RelationshipConnectionFactory::CONNECTION_SLAVE,
                    RelationshipConnectionFactory::CONNECTION_QUOTE_MAIN,
                    RelationshipConnectionFactory::CONNECTION_QUOTE_SLAVE,
                    RelationshipConnectionFactory::CONNECTION_SALES_MAIN,
                    RelationshipConnectionFactory::CONNECTION_SALES_SLAVE,
                ]),
                'envDbConfig' => [
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
                        'sale' => [
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
                        'sale' => [
                            'host' => 'custom_host_sales_slave:3388',
                            'username' => 'custom_username_sales_slave',
                            'dbname' => 'custom_dbname_sales_slave',
                            'password' => 'custom_password_sales_slave',
                        ],
                    ],
                ],
                'setSlave' => true,
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
                        'sale' => [
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
                        ],
                        'sale' => ['host' => 'some_host_sales_slave:3311',
                            'username' => 'some_username_sales_slave',
                            'dbname' => 'some_dbname_sales_slave',
                            'password' => 'some_password_sales_slave',
                            'model' => 'mysql4',
                            'engine' => 'innodb',
                            'initStatements' => 'SET NAMES utf8;',
                            'active' => '1',
                        ],
                    ],
                ],
            ]
        ];
    }
}
