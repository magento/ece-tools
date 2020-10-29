<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Test\Unit\Service;

use Magento\MagentoCloud\DB\ConnectionInterface;
use Magento\MagentoCloud\DB\Data\ConnectionTypes;
use Magento\MagentoCloud\Service\Database;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @inheritdoc
 */
class DatabaseTest extends TestCase
{

    /**
     * @var Database
     */
    private $database;

    /**
     * @var ConnectionTypes|MockObject
     */
    private $connectionTypeMock;

    /**
     * @var ConnectionInterface|MockObject
     */
    private $connectionMock;

    /**
     * @inheritdoc
     */
    public function setUp()
    {
        $this->connectionTypeMock = $this->createMock(ConnectionTypes::class);
        $this->connectionMock = $this->getMockForAbstractClass(ConnectionInterface::class);

        $this->database = new Database(
            $this->connectionTypeMock,
            $this->connectionMock
        );
    }

    public function testGetConfiguration(): void
    {
        $connection = [
            'host' => '127.0.0.1',
            'port' => '3306',
        ];

        $this->connectionTypeMock->expects($this->once())
            ->method('getConfiguration')
            ->willReturn($connection);

        $this->assertSame(
            $connection,
            $this->database->getConfiguration()
        );
    }

    /**
     * @param array $config
     * @param string $expectedVersion
     *
     * @dataProvider getVersionFromConfigDataProvider
     */
    public function testGetVersionFromConfig(array $config, string $expectedVersion): void
    {
        $this->connectionTypeMock->expects($this->once())
            ->method('getConfiguration')
            ->willReturn($config);
        $this->connectionMock->expects($this->never())
            ->method('selectOne');

        $this->assertEquals($expectedVersion, $this->database->getVersion());
    }

    /**
     * Data provider for testGetVersionFromConfig
     * @return array
     */
    public function getVersionFromConfigDataProvider(): array
    {
        return [
            [
                [
                    'host' => '127.0.0.1',
                    'port' => '3306',
                    'type' => 'mysql:10.2',
                ],
                '10.2'
            ],
            [
                ['host' => ''],
                '0'
            ]
        ];
    }

    /**
     * @param array $version
     * @param string $expectedResult
     * @throws \Magento\MagentoCloud\Service\ServiceException
     *
     * @dataProvider getVersionDataProvider
     */
    public function testGetVersion(array $version, string $expectedResult): void
    {
        $this->connectionTypeMock->expects($this->once())
            ->method('getConfiguration')
            ->willReturn(
                [
                    'host' => '127.0.0.1',
                    'port' => '3306',
                ]
            );

        $this->connectionMock->expects($this->once())
            ->method('selectOne')
            ->with('SELECT VERSION() as version')
            ->willReturn($version);

        $this->assertEquals($expectedResult, $this->database->getVersion());
    }

    /**
     * Data provider for testGetVersion
     * @return array
     */
    public function getVersionDataProvider(): array
    {
        return [
            [['version' => '10.2.33-MariaDB-10.2.33+maria~stretch-lo'], '10.2'],
            [['version' => '10.3.20-MariaDB-1:10.3.20+maria~jessie'], '10.3'],
            [['version' => 's10.3.20-MariaDB-1:10.3.20+maria~jessie'], '0'],
            [['version' => ''], '0'],
            [['version' => '10.version'], '0'],
            [[], '0'],
        ];
    }
}
