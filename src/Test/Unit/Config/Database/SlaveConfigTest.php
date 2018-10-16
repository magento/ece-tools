<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Unit\Config\Database;

use Magento\MagentoCloud\Config\Environment;
use Magento\MagentoCloud\DB\Data\ConnectionInterface;
use Magento\MagentoCloud\Config\Database\SlaveConfig;
use PHPUnit\Framework\TestCase;
use PHPUnit_Framework_MockObject_MockObject as Mock;

/**
 * @inheritdoc
 */
class SlaveConfigTest extends TestCase
{
    /**
     * @param array $connectionData
     * @param array $expectedConfig
     * @dataProvider getDataProvider
     */
    public function testGet(
        array $connectionData,
        array $expectedConfig
    ) {
        /* @var Mock|ConnectionInterface $connectionDataMock */
        $connectionDataMock = $this->getMockForAbstractClass(ConnectionInterface::class, [
            $connectionData
        ]);

        $dbSlaveConfig = new SlaveConfig($connectionDataMock);

        $this->assertEquals($expectedConfig, $dbSlaveConfig->get());
    }

    /**
     * @return array
     */
    public function getDataProvider(): array
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
        $relationshipsWithoutSlave['database-slave'] = [];

        return [
            [
                [],
                []
            ],
            [
                [
                    'host' => 'localhost',
                    'port' => '3306',
                    'path' => 'magento',
                    'username' => 'user',
                    'password' => 'password',
                ],
                [
                    'host' => 'slave.host:slave.port',
                    'username' => 'slave.user',
                    'dbname' => 'slave.name',
                    'password' => 'slave.pswd',
                    'model' => 'mysql4',
                    'engine' => 'innodb',
                    'initStatements' => 'SET NAMES utf8;',
                    'active' => '1',
                ]
            ],
            [
                $relationshipsWithoutSlave,
                [
                    'host' => 'localhost:3306',
                    'username' => 'user',
                    'dbname' => 'magento',
                    'password' => 'password',
                    'model' => 'mysql4',
                    'engine' => 'innodb',
                    'initStatements' => 'SET NAMES utf8;',
                    'active' => '1',
                ]
            ],
        ];
    }
}
