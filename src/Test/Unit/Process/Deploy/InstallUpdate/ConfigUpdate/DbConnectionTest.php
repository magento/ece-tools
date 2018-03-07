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
     * @var DbConnection
     */
    private $process;

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

        $this->environmentMock->expects($this->any())
            ->method('getRelationships')
            ->willReturn([
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
            ]);

        $readConnection = new ReadConnection($this->environmentMock);

        $this->process = new DbConnection(
            $this->environmentMock,
            $readConnection,
            $this->deployConfigMock,
            $this->configWriterMock,
            $this->loggerMock
        );
    }

    /**
     * @param $setSlave
     * @param $resultConnectionData
     *
     * @dataProvider executeDataProvider
     */
    public function testExecute($setSlave, $resultConnectionData)
    {
        $this->deployConfigMock->expects($this->once())
            ->method('get')
            ->with(DeployInterface::VAR_MYSQL_READ_DISTRIBUTION)
            ->willReturn($setSlave);

        $this->configWriterMock->expects($this->once())
            ->method('update')
            ->with($resultConnectionData);

        $this->process->execute();
    }

    /**
     * Data provider for testExecute.
     * Return data for 2 parameters:
     * 1 - value for VAR_MYSQL_READ_DISTRIBUTION variable
     * 2 - result connection data for configuration file
     *
     * @return array
     */
    public function executeDataProvider()
    {
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
        return [
            [
                true,
                [
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
                ],
            ],
            [
                false,
                [
                    'db' => [
                        'connection' => [
                            'default' => $masterConnection,
                            'indexer' => $masterConnection,
                        ],
                    ],
                    'resource' => [
                        'default_setup' => [
                            'connection' => 'default',
                        ],
                    ],
                ],
            ],
        ];
    }
}
