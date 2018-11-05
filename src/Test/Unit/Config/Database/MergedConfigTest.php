<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Unit\Config\Database;

use Magento\MagentoCloud\Config\ConfigMerger;
use Magento\MagentoCloud\Config\Database\MergedConfig;
use Magento\MagentoCloud\Config\Database\SlaveConfig;
use Magento\MagentoCloud\Config\Deploy\Reader as ConfigReader;
use Magento\MagentoCloud\Config\Stage\DeployInterface;
use Magento\MagentoCloud\DB\Data\ConnectionInterface;
use Magento\MagentoCloud\DB\Data\RelationshipConnectionFactory;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @inheritdoc
 */
class MergedConfigTest extends TestCase
{
    /**
     * @var DeployInterface|MockObject
     */
    private $stageConfigMock;

    /**
     * @var SlaveConfig|MockObject
     */
    private $slaveConfigMock;

    /**
     * @var ConnectionInterface|MockObject
     */
    private $connectionDataMock;

    /**
     * @var ConfigReader|MockObject
     */
    private $configReaderMock;

    /**
     * @var RelationshipConnectionFactory|MockObject
     */
    private $connectionFactoryMock;

    /**
     * @var MergedConfig
     */
    private $mergedConfig;

    protected function setUp()
    {
        $this->connectionDataMock = $this->getMockForAbstractClass(ConnectionInterface::class);
        $this->connectionFactoryMock = $this->createMock(RelationshipConnectionFactory::class);
        $this->connectionFactoryMock->expects($this->any())
            ->method('create')
            ->willReturn($this->connectionDataMock);
        $this->configReaderMock = $this->createMock(ConfigReader::class);
        $this->slaveConfigMock = $this->createMock(SlaveConfig::class);
        $this->stageConfigMock = $this->getMockForAbstractClass(DeployInterface::class);

        $this->mergedConfig = new MergedConfig(
            $this->connectionFactoryMock,
            $this->configReaderMock,
            $this->slaveConfigMock,
            $this->stageConfigMock,
            new ConfigMerger()
        );
    }

    /**
     * @param array $relationShipConnectionData
     * @param array $envDbConfig
     * @param array $slaveConfiguration
     * @param boolean $setSlave
     * @param array $expectedConfig
     * @dataProvider getDataProvider
     */
    public function testGet(
        array $relationShipConnectionData,
        array $envDbConfig,
        array $slaveConfiguration,
        $setSlave,
        array $expectedConfig
    ) {
        $this->setConnectionData($relationShipConnectionData);
        $this->slaveConfigMock->expects($this->any())
            ->method('get')
            ->willReturn($slaveConfiguration);
        $this->stageConfigMock->expects($this->any())
            ->method('get')
            ->willReturnMap([
                [DeployInterface::VAR_DATABASE_CONFIGURATION, $envDbConfig],
                [DeployInterface::VAR_MYSQL_USE_SLAVE_CONNECTION, $setSlave],
            ]);

        $this->assertEquals($expectedConfig, $this->mergedConfig->get());
    }

    /**
     * Data provider for testExecute.
     *
     * Return data for 2 parameters:
     * 1 - relationship connection data
     * 2 - custom db configuration
     * 3 - slave configuration
     * 4 - value for VAR_MYSQL_USE_SLAVE_CONNECTION variable
     * 5 - result of updated config data for configuration file
     *
     * @return array
     *
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function getDataProvider()
    {
        $connectionData = [
            'host' => 'localhost',
            'port' => '3306',
            'path' => 'magento',
            'username' => 'user',
            'password' => 'password',
        ];

        $slaveConfig = [
            'host' => 'slave.host:slave.port',
            'username' => 'slave.user',
            'dbname' => 'slave.name',
            'password' => 'slave.pswd',
            'model' => 'mysql4',
            'engine' => 'innodb',
            'initStatements' => 'SET NAMES utf8;',
            'active' => '1',
        ];

        return [
            'default connection without slave' => [
                $connectionData,
                [],
                $slaveConfig,
                false,
                [
                    'connection' => [
                        'default' => [
                            'username' => 'user',
                            'host' => 'localhost',
                            'dbname' => 'magento',
                            'password' => 'password',
                        ],
                        'indexer' => [
                            'username' => 'user',
                            'host' => 'localhost',
                            'dbname' => 'magento',
                            'password' => 'password',
                        ],
                    ],
                ],
            ],
            'default connection with slave' => [
                $connectionData,
                [],
                $slaveConfig,
                true,
                [
                    'connection' => [
                        'default' => [
                            'username' => 'user',
                            'host' => 'localhost',
                            'dbname' => 'magento',
                            'password' => 'password',
                        ],
                        'indexer' => [
                            'username' => 'user',
                            'host' => 'localhost',
                            'dbname' => 'magento',
                            'password' => 'password',
                        ],
                    ],
                    'slave_connection' => ['default' => $slaveConfig],
                ],
            ],
            'custom environment db configuration only merge option' => [
                $connectionData,
                [
                    '_merge' => true,
                ],
                $slaveConfig,
                true,
                [
                    'connection' => [
                        'default' => [
                            'username' => 'user',
                            'host' => 'localhost',
                            'dbname' => 'magento',
                            'password' => 'password',
                        ],
                        'indexer' => [
                            'username' => 'user',
                            'host' => 'localhost',
                            'dbname' => 'magento',
                            'password' => 'password',
                        ],
                    ],
                    'slave_connection' => ['default' => $slaveConfig],
                ],
            ],
            'custom environment db configuration without merge' => [
                $connectionData,
                [
                    'connection' => [
                        'default' => [
                            'host' => 'test',
                            'dbname' => 'test',
                            'driver_options'=> [\PDO::MYSQL_ATTR_LOCAL_INFILE => 1],
                        ],
                    ],
                    'slave_connection' => [
                        ['default' => $slaveConfig],
                    ],
                ],
                $slaveConfig,
                true,
                [
                    'connection' => [
                        'default' => [
                            'host' => 'test',
                            'dbname' => 'test',
                            'driver_options'=> [\PDO::MYSQL_ATTR_LOCAL_INFILE => 1],
                        ],
                    ],
                    'slave_connection' => [
                        ['default' => $slaveConfig],
                    ],
                ],
            ],
            'custom environment db configuration with merge and without slave' => [
                $connectionData,
                [
                    'connection' => [
                        'default' => [
                            'host' => 'test.host',
                            'dbname' => 'test.dbname',
                            'driver_options'=> [\PDO::MYSQL_ATTR_LOCAL_INFILE => 1],
                        ],
                        'indexer' => [
                            'driver_options'=> [\PDO::MYSQL_ATTR_LOCAL_INFILE => 1],
                        ],
                    ],
                    '_merge' => true,
                ],
                $slaveConfig,
                false,
                [
                    'connection' => [
                        'default' => [
                            'username' => 'user',
                            'host' => 'test.host',
                            'dbname' => 'test.dbname',
                            'password' => 'password',
                            'driver_options'=> [\PDO::MYSQL_ATTR_LOCAL_INFILE => 1],
                        ],
                        'indexer' => [
                            'username' => 'user',
                            'host' => 'localhost',
                            'dbname' => 'magento',
                            'password' => 'password',
                            'driver_options'=> [\PDO::MYSQL_ATTR_LOCAL_INFILE => 1],
                        ],
                    ],
                ],
            ],
            'custom environment db configuration with merge set to false and without slave' => [
                $connectionData,
                [
                    'connection' => [
                        'default' => [
                            'host' => 'test.host',
                            'dbname' => 'test.dbname',
                            'driver_options'=> [\PDO::MYSQL_ATTR_LOCAL_INFILE => 1],
                        ],
                        'indexer' => [
                            'driver_options'=> [\PDO::MYSQL_ATTR_LOCAL_INFILE => 1],
                        ],
                    ],
                    '_merge' => false,
                ],
                $slaveConfig,
                false,
                [
                    'connection' => [
                        'default' => [
                            'host' => 'test.host',
                            'dbname' => 'test.dbname',
                            'driver_options'=> [\PDO::MYSQL_ATTR_LOCAL_INFILE => 1],
                        ],
                        'indexer' => [
                            'driver_options'=> [\PDO::MYSQL_ATTR_LOCAL_INFILE => 1],
                        ],
                    ],
                ],
            ],
            'custom environment db configuration with merge and with slave' => [
                $connectionData,
                [
                    'connection' => [
                        'default' => [
                            'driver_options'=> [\PDO::MYSQL_ATTR_LOCAL_INFILE => 1],
                        ],
                        'indexer' => [
                            'driver_options'=> [\PDO::MYSQL_ATTR_LOCAL_INFILE => 1],
                        ],
                    ],
                    '_merge' => true,
                ],
                $slaveConfig,
                true,
                [
                    'connection' => [
                        'default' => [
                            'username' => 'user',
                            'host' => 'localhost',
                            'dbname' => 'magento',
                            'password' => 'password',
                            'driver_options'=> [\PDO::MYSQL_ATTR_LOCAL_INFILE => 1],
                        ],
                        'indexer' => [
                            'username' => 'user',
                            'host' => 'localhost',
                            'dbname' => 'magento',
                            'password' => 'password',
                            'driver_options'=> [\PDO::MYSQL_ATTR_LOCAL_INFILE => 1],
                        ],
                    ],
                    'slave_connection' => ['default' => $slaveConfig],
                ],
            ],
            'custom environment db configuration with merge, with slave, and host changed' => [
                $connectionData,
                [
                    'connection' => [
                        'default' => [
                            'host' => 'test.host',
                            'driver_options'=> [\PDO::MYSQL_ATTR_LOCAL_INFILE => 1],
                        ],
                        'indexer' => [
                            'driver_options'=> [\PDO::MYSQL_ATTR_LOCAL_INFILE => 1],
                        ],
                    ],
                    '_merge' => true,
                ],
                $slaveConfig,
                true,
                [
                    'connection' => [
                        'default' => [
                            'username' => 'user',
                            'host' => 'test.host',
                            'dbname' => 'magento',
                            'password' => 'password',
                            'driver_options'=> [\PDO::MYSQL_ATTR_LOCAL_INFILE => 1],
                        ],
                        'indexer' => [
                            'username' => 'user',
                            'host' => 'localhost',
                            'dbname' => 'magento',
                            'password' => 'password',
                            'driver_options'=> [\PDO::MYSQL_ATTR_LOCAL_INFILE => 1],
                        ],
                    ],
                ],
            ],
            'custom environment db configuration with custom slave connection and without merge' => [
                $connectionData,
                [
                    'connection' => [
                        'default' => [
                            'host' => 'test',
                            'dbname' => 'test',
                            'driver_options'=> [\PDO::MYSQL_ATTR_LOCAL_INFILE => 1],
                        ],
                    ],
                    'slave_connection' => [
                        'default' => [
                            'host' => 'custom_slave.host:custom_slave.port',
                            'username' => 'custom_slave.user',
                            'dbname' => 'custom_slave.name',
                            'password' => 'custom_slave.pswd',
                        ],
                    ],
                ],
                $slaveConfig,
                true,
                [
                    'connection' => [
                        'default' => [
                            'host' => 'test',
                            'dbname' => 'test',
                            'driver_options'=> [\PDO::MYSQL_ATTR_LOCAL_INFILE => 1],
                        ],
                    ],
                    'slave_connection' => [
                        'default' => [
                            'host' => 'custom_slave.host:custom_slave.port',
                            'username' => 'custom_slave.user',
                            'dbname' => 'custom_slave.name',
                            'password' => 'custom_slave.pswd',
                        ],
                    ],
                ],
            ],
            'environment db configuration with custom slave connection and with merge and use slave connection' => [
                $connectionData,
                [
                    'slave_connection' => [
                        'default' => [
                            'host' => 'custom_slave.host:custom_slave.port',
                            'username' => 'custom_slave.user',
                            'dbname' => 'custom_slave.name',
                            'password' => 'custom_slave.pswd',
                        ],
                    ],
                    '_merge' => true
                ],
                $slaveConfig,
                true,
                [
                    'connection' => [
                        'default' => [
                            'username' => 'user',
                            'host' => 'localhost',
                            'dbname' => 'magento',
                            'password' => 'password',
                        ],
                        'indexer' => [
                            'username' => 'user',
                            'host' => 'localhost',
                            'dbname' => 'magento',
                            'password' => 'password',
                        ],
                    ],
                    'slave_connection' => [
                        'default' => [
                            'host' => 'custom_slave.host:custom_slave.port',
                            'username' => 'custom_slave.user',
                            'dbname' => 'custom_slave.name',
                            'password' => 'custom_slave.pswd',
                            'model' => 'mysql4',
                            'engine' => 'innodb',
                            'initStatements' => 'SET NAMES utf8;',
                            'active' => '1',
                        ],
                    ],
                ],
            ],
            'environment db config with custom slave connection and with merge and use without slave connection' => [
                $connectionData,
                [
                    'slave_connection' => [
                        'default' => [
                            'host' => 'custom_slave.host:custom_slave.port',
                            'username' => 'custom_slave.user',
                            'dbname' => 'custom_slave.name',
                            'password' => 'custom_slave.pswd',
                        ],
                    ],
                    '_merge' => true
                ],
                $slaveConfig,
                false,
                [
                    'connection' => [
                        'default' => [
                            'username' => 'user',
                            'host' => 'localhost',
                            'dbname' => 'magento',
                            'password' => 'password',
                        ],
                        'indexer' => [
                            'username' => 'user',
                            'host' => 'localhost',
                            'dbname' => 'magento',
                            'password' => 'password',
                        ],
                    ],
                    'slave_connection' => [
                        'default' => [
                            'host' => 'custom_slave.host:custom_slave.port',
                            'username' => 'custom_slave.user',
                            'dbname' => 'custom_slave.name',
                            'password' => 'custom_slave.pswd',
                        ],
                    ],
                ],
            ],
        ];
    }

    private function setConnectionData(array $relationShipConnectionData)
    {
        $this->connectionDataMock->expects($this->any())
            ->method('getHost')
            ->willReturn($relationShipConnectionData['host']);
        $this->connectionDataMock->expects($this->any())
            ->method('getPort')
            ->willReturn($relationShipConnectionData['host']);
        $this->connectionDataMock->expects($this->any())
            ->method('getDbName')
            ->willReturn($relationShipConnectionData['path']);
        $this->connectionDataMock->expects($this->any())
            ->method('getUser')
            ->willReturn($relationShipConnectionData['username']);
        $this->connectionDataMock->expects($this->any())
            ->method('getPassword')
            ->willReturn($relationShipConnectionData['password']);
    }
}
