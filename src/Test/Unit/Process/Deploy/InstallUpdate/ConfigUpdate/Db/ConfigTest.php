<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Unit\Process\Deploy\InstallUpdate\ConfigUpdate\Db;

use Magento\MagentoCloud\Config\ConfigMerger;
use Magento\MagentoCloud\Config\Environment;
use Magento\MagentoCloud\Config\Stage\DeployInterface;
use Magento\MagentoCloud\Process\Deploy\InstallUpdate\ConfigUpdate\Db\Config;
use Magento\MagentoCloud\Process\Deploy\InstallUpdate\ConfigUpdate\Db\SlaveConfig;
use PHPUnit\Framework\TestCase;
use PHPUnit_Framework_MockObject_MockObject as Mock;
use Psr\Log\LoggerInterface;

/**
 * @inheritdoc
 */
class ConfigTest extends TestCase
{
    /**
     * @var DeployInterface|Mock
     */
    private $stageConfigMock;

    /**
     * @var LoggerInterface|Mock
     */
    private $loggerMock;

    /**
     * @var SlaveConfig|Mock
     */
    private $slaveConfigMock;

    protected function setUp()
    {
        $this->stageConfigMock = $this->getMockForAbstractClass(DeployInterface::class);
        $this->loggerMock = $this->getMockForAbstractClass(LoggerInterface::class);
        $this->slaveConfigMock = $this->createMock(SlaveConfig::class);
    }

    /**
     * @param array $magentoRelationShips
     * @param array $envDbConfig
     * @param array $slaveConfiguration
     * @param boolean $setSlave
     * @param array $expectedConfig
     * @dataProvider getDataProvider
     */
    public function testGet(
        array $magentoRelationShips,
        array $envDbConfig,
        array $slaveConfiguration,
        $setSlave,
        array $expectedConfig
    ) {
        /** @var Environment|Mock $dbConfigEnvironmentMock */
        $dbConfigEnvironmentMock = $this->createPartialMock(Environment::class, ['getRelationships']);
        $dbConfigEnvironmentMock->expects($this->any())
            ->method('getRelationships')
            ->willReturn($magentoRelationShips);
        $this->slaveConfigMock->expects($this->any())
            ->method('get')
            ->willReturn($slaveConfiguration);
        $this->stageConfigMock->expects($this->any())
            ->method('get')
            ->withConsecutive(
                [DeployInterface::VAR_DATABASE_CONFIGURATION],
                [DeployInterface::VAR_MYSQL_USE_SLAVE_CONNECTION]
            )
            ->willReturnOnConsecutiveCalls(
                $envDbConfig,
                $setSlave
            );

        $dbConfig = new Config(
            $dbConfigEnvironmentMock,
            $this->slaveConfigMock,
            $this->stageConfigMock,
            $this->loggerMock,
            new ConfigMerger()
        );

        $this->assertEquals($expectedConfig, $dbConfig->get());
    }

    /**
     * Data provider for testExecute.
     *
     * Return data for 2 parameters:
     * 1 - relationships data
     * 2 - custom db configuration
     * 2 - slave configuration
     * 3 - value for VAR_MYSQL_USE_SLAVE_CONNECTION variable
     * 4 - result of updated config data for configuration file
     *
     * @return array
     *
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function getDataProvider()
    {
        $relationships = [
            'database' => [
                0 => [
                    'host' => 'localhost',
                    'port' => '3306',
                    'path' => 'magento',
                    'username' => 'user',
                    'password' => 'password',
                ]
            ],
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
                $relationships,
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
                $relationships,
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
                $relationships,
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
                $relationships,
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
                $relationships,
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
                $relationships,
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
                $relationships,
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
                $relationships,
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
        ];
    }
}
