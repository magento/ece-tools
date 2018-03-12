<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Unit\Process\Deploy\InstallUpdate\ConfigUpdate;

use Magento\MagentoCloud\Config\Stage\DeployInterface;
use Magento\MagentoCloud\DB\Data\ReadConnection;
use Magento\MagentoCloud\Process\Deploy\InstallUpdate\ConfigUpdate\DbConnection;
use PHPUnit\Framework\TestCase;
use Magento\MagentoCloud\Config\Environment;
use Magento\MagentoCloud\Config\Deploy\Reader as ConfigReader;
use Magento\MagentoCloud\Config\Deploy\Writer as ConfigWriter;
use Psr\Log\LoggerInterface;
use PHPUnit_Framework_MockObject_MockObject as Mock;

/**
 * @inheritdoc
 */
class DbConnectionTest extends TestCase
{
    /**
     * @var Environment|Mock
     */
    private $environmentMock;

    /**
     * @var DeployInterface|Mock
     */
    private $deployConfigMock;

    /**
     * @var LoggerInterface|Mock
     */
    private $loggerMock;

    /**
     * @var ConfigWriter|Mock
     */
    private $configWriterMock;

    /**
     * @var ConfigReader|Mock
     */
    private $configReaderMock;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        $this->environmentMock = $this->getMockBuilder(Environment::class)
            ->setMethods(['getRelationships'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->deployConfigMock = $this->getMockBuilder(DeployInterface::class)
            ->getMockForAbstractClass();
        $this->loggerMock = $this->getMockBuilder(LoggerInterface::class)
            ->getMockForAbstractClass();
        $this->configWriterMock = $this->createMock(ConfigWriter::class);
        $this->configReaderMock = $this->createMock(ConfigReader::class);
    }

    /**
     * @param $relationships
     * @return DbConnection
     */
    public function getProcess($relationships)
    {
        $this->environmentMock->expects($this->any())
            ->method('getRelationships')
            ->willReturn($relationships);

        $readConnection = new ReadConnection($this->environmentMock);

        return new DbConnection(
            $this->environmentMock,
            $readConnection,
            $this->deployConfigMock,
            $this->configWriterMock,
            $this->configReaderMock,
            $this->loggerMock
        );
    }

    /**
     * @param $relationshipData array
     * @param $configData array
     * @param $setSlave boolean
     * @param $resultConfigData array
     *
     * @dataProvider executeDataProvider
     */
    public function testExecute($relationshipData, $configData, $setSlave, $resultConfigData)
    {
        $this->configReaderMock->expects($this->once())
            ->method('read')
            ->willReturn($configData);

        $this->deployConfigMock->expects($this->once())
            ->method('get')
            ->with(DeployInterface::VAR_MYSQL_READ_DISTRIBUTION)
            ->willReturn($setSlave);

        $this->configWriterMock->expects($this->once())
            ->method('create')
            ->with($resultConfigData);

        $process = $this->getProcess($relationshipData);

        $process->execute();
    }

    /**
     * Data provider for testExecute.
     *
     * Return data for 2 parameters:
     * 1 - relationships data
     * 2 - previous config data
     * 3 - value for VAR_MYSQL_READ_DISTRIBUTION variable
     * 4 - result of updated config data for configuration file
     *
     * @return array
     */
    public function executeDataProvider()
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
            'database-slave' => [
                0 => [
                    'host' => 'slave.host',
                    'port' => 'slave.port',
                    'path' => 'slave.name',
                    'username' => 'slave.user',
                    'password' => 'slave.pswd',
                ]
            ],
        ];
        $relationshipsWithoutSlave = $relationships;
        $relationshipsWithoutSlave['database-slave'][0] = [];

        $masterConnection = [
            'username' => 'user',
            'host' => 'localhost',
            'dbname' => 'magento',
            'password' => 'password'
        ];

        $slaveConnection = [
            'host' => 'slave.host:slave.port',
            'username' => 'slave.user',
            'dbname' => 'slave.name',
            'password' => 'slave.pswd',
            'model' => 'mysql4',
            'engine' => 'innodb',
            'initStatements' => 'SET NAMES utf8;',
            'active' => '1',
        ];

        $baseResult = [
            'db' => [
                'connection' => [
                    'default' => $masterConnection,
                    'indexer' => $masterConnection,
                ]
            ],
            'resource' => [
                'default_setup' => [
                    'connection' => 'default',
                ],
            ],
        ];

        $resultWithSlave = [
            'db' => [
                'connection' => [
                    'default' => $masterConnection,
                    'indexer' => $masterConnection,
                ],
                'slave_connection' => [
                    'default' => $slaveConnection,
                ]
            ],
            'resource' => [
                'default_setup' => [
                    'connection' => 'default',
                ],
            ],
        ];

        $ownConnection = [
            'db' => [
                'own_connection' => ['some connection data'],
            ]
        ];

        return [
            [
                $relationships,
                [],
                false,
                $baseResult
            ],
            [
                $relationships,
                [
                    'db' => [
                        'connection' => [
                            'default' => 'some connection data'
                        ],
                    ],
                ],
                false,
                $baseResult
            ],
            [
                $relationships,
                [
                    'db' => [
                        'slave_connection' => [
                            'default' => 'some connection data'
                        ],
                    ],
                ],
                false,
                $baseResult
            ],
            [
                $relationships,
                $ownConnection,
                false,
                array_replace_recursive($baseResult, $ownConnection),
            ],
            [
                $relationships,
                [],
                true,
                $resultWithSlave
            ],
            [
                $relationships,
                [
                    'db' => [
                        'connection' => [
                            'default' => 'some connection data'
                        ],
                    ],
                ],
                true,
                $resultWithSlave
            ],
            [
                $relationships,
                [
                    'db' => [
                        'slave_connection' => [
                            'default' => 'some connection data'
                        ],
                    ],
                ],
                true,
                $resultWithSlave
            ],
            [
                $relationships,
                $ownConnection,
                true,
                array_replace_recursive($resultWithSlave, $ownConnection),
            ],
            [
                $relationshipsWithoutSlave,
                [
                    'db' => [
                        'slave_connection' => [
                            'default' => 'some connection data'
                        ],
                    ],
                ],
                true,
                $baseResult,
            ],
        ];
    }
}
