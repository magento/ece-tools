<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Unit\Config\Database;

use Magento\MagentoCloud\Config\Database\SlaveConfig;
use Magento\MagentoCloud\DB\Data\RelationshipConnectionFactory;
use Magento\MagentoCloud\DB\Data\ConnectionInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

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
        /** @var RelationshipConnectionFactory $connectionFactoryMock */
        $connectionFactoryMock = $this->createMock(RelationshipConnectionFactory::class);
        /** @var ConnectionInterface|MockObject $connectionDataMock */
        $connectionDataMock = $this->getMockForAbstractClass(ConnectionInterface::class);
        $connectionDataMock->expects($this->any())
            ->method('getHost')
            ->willReturn($connectionData['host'] ?? '');
        $connectionDataMock->expects($this->any())
            ->method('getPort')
            ->willReturn($connectionData['port'] ?? '');
        $connectionDataMock->expects($this->any())
            ->method('getDbName')
            ->willReturn($connectionData['path'] ?? '');
        $connectionDataMock->expects($this->any())
            ->method('getUser')
            ->willReturn($connectionData['username'] ?? '');
        $connectionDataMock->expects($this->any())
            ->method('getPassword')
            ->willReturn($connectionData['password'] ?? '');
        $connectionFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($connectionDataMock);

        $dbSlaveConfig = new SlaveConfig($connectionFactoryMock);

        $this->assertEquals($expectedConfig, $dbSlaveConfig->get());
    }

    /**
     * @return array
     */
    public function getDataProvider(): array
    {
        return [
            [
                [],
                []
            ],
            [
                [
                    'host' => '127.0.0.1',
                    'port' => '',
                    'path' => 'magento',
                    'username' => '',
                    'password' => 'password',
                ],
                [
                    'host' => '127.0.0.1',
                    'username' => '',
                    'dbname' => 'magento',
                    'password' => 'password',
                    'model' => 'mysql4',
                    'engine' => 'innodb',
                    'initStatements' => 'SET NAMES utf8;',
                    'active' => '1',
                ]
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
