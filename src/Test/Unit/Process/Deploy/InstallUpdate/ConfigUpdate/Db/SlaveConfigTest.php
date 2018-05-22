<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Unit\Process\Deploy\InstallUpdate\ConfigUpdate\Db;

use Magento\MagentoCloud\Config\Environment;
use Magento\MagentoCloud\DB\Data\ReadConnection;
use Magento\MagentoCloud\Process\Deploy\InstallUpdate\ConfigUpdate\Db\SlaveConfig;
use PHPUnit\Framework\TestCase;
use PHPUnit_Framework_MockObject_MockObject as Mock;

/**
 * @inheritdoc
 */
class SlaveConfigTest extends TestCase
{
    /**
     * @param array $relationships
     * @param array $expectedConfig
     * @dataProvider getDataProvider
     */
    public function testGet(
        array $relationships,
        array $expectedConfig
    ) {
        /** @var Environment|Mock $readConnectionEnvironmentMock */
        $readConnectionEnvironmentMock = $this->createPartialMock(Environment::class, ['getRelationships']);
        $readConnectionEnvironmentMock->expects($this->any())
            ->method('getRelationships')
            ->willReturn($relationships);

        $readConnection = new ReadConnection($readConnectionEnvironmentMock);

        $dbSlaveConfig = new SlaveConfig($readConnection);

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
                $relationships,
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
