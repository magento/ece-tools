<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Unit\DB\Data;

use Magento\MagentoCloud\Config\Environment;
use Magento\MagentoCloud\DB\Data\ReadConnection;
use PHPUnit\Framework\TestCase;
use PHPUnit_Framework_MockObject_MockObject as Mock;

/**
 * @inheritdoc
 */
class ConnectionTest extends TestCase
{
    /**
     * @var Environment|Mock
     */
    private $environmentMock;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        $this->environmentMock = $this->getMockBuilder(Environment::class)
            ->disableOriginalConstructor()
            ->getMock();
    }

    /**
     * @param $slave array
     * @param $master array
     * @param $host string
     * @param $port string
     * @param $dbName string
     * @param $user string
     * @param $password string
     *
     * @dataProvider connectionDataProvider
     */
    public function testConnectionData($slave, $master, $host, $port, $dbName, $user, $password)
    {
        $this->environmentMock->expects($this->any())
            ->method('getRelationship')
            ->willReturnMap(
                [
                    ['database-slave', $slave],
                    ['database', $master],
                ]
            );

        $model = new ReadConnection($this->environmentMock);

        $this->assertEquals($host, $model->getHost());
        $this->assertEquals($port, $model->getPort());
        $this->assertEquals($dbName, $model->getDbName());
        $this->assertEquals($user, $model->getUser());
        $this->assertEquals($password, $model->getPassword());
    }

    /**
     * Return next data:
     * 1 - data for 'database-slave' connection
     * 2 - data for 'database' connection
     * 3 - result for host
     * 4 - result for port
     * 5 - result for DB name
     * 6 - result for user
     * 7 - result for password
     *
     * @return array
     */
    public function connectionDataProvider()
    {
        $fullMasterData = [
            'host' => 'm.host',
            'port' => 'm.port',
            'path' => 'm.path',
            'username' => 'm.user',
            'password' => 'm.pswd',
        ];
        $fullSlaveData = [
            'host' => 's.host',
            'port' => 's.port',
            'path' => 's.path',
            'username' => 's.user',
            'password' => 's.pswd',
        ];

        return [
            [[], [], '', '', '', '', '',],
            [
                [['other slave data']],
                [],
                '',
                '',
                '',
                '',
                '',
            ],
            [
                [],
                [['other master data']],
                '',
                '',
                '',
                '',
                '',
            ],
            [
                [['host' => 's.host']],
                [$fullMasterData, ],
                's.host',
                '',
                '',
                '',
                '',
            ],
            [
                [['other slave data']],
                [$fullMasterData, ],
                '',
                '',
                '',
                '',
                '',
            ],
            [
                [$fullSlaveData],
                [$fullMasterData],
                's.host',
                's.port',
                's.path',
                's.user',
                's.pswd',
            ],
            [
                [[]],
                [$fullMasterData],
                '',
                '',
                '',
                '',
                '',
            ],
            [
                [],
                [$fullMasterData],
                'm.host',
                'm.port',
                'm.path',
                'm.user',
                'm.pswd',
            ],
        ];
    }
}
