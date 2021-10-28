<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Test\Unit\DB;

use Magento\MagentoCloud\Config\Database\DbConfig;
use Magento\MagentoCloud\DB\Connection;
use Magento\MagentoCloud\DB\Data\ConnectionFactory;
use Magento\MagentoCloud\DB\Data\ConnectionInterface;
use Magento\MagentoCloud\DB\PDOException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * @inheritdoc
 */
class ConnectionTest extends TestCase
{
    /**
     * @var Connection
     */
    private $connection;

    /**
     * @var \PDO|MockObject
     */
    private $pdoMock;

    /**
     * @var \PDOStatement|MockObject
     */
    private $statementMock;

    /**
     * @var LoggerInterface|MockObject
     */
    private $loggerMock;

    /**
     * @var ConnectionInterface|MockObject
     */
    private $connectionDataMock;

    /**
     * @var DbConfig|MockObject
     */
    private $dbConfigMock;

    /**
     * {@inheritdoc}
     *
     * @throws \ReflectionException
     */
    protected function setUp(): void
    {
        $this->pdoMock = $this->createMock(\PDO::class);
        $this->statementMock = $this->createMock(\PDOStatement::class);
        $this->dbConfigMock = $this->createMock(DbConfig::class);
        $this->loggerMock = $this->getMockForAbstractClass(LoggerInterface::class);
        $this->connectionDataMock = $this->getMockForAbstractClass(ConnectionInterface::class);

        /** @var ConnectionFactory|MockObject $connectionFactoryMock */
        $connectionFactoryMock = $this->createMock(ConnectionFactory::class);
        $connectionFactoryMock->expects($this->any())
            ->method('create')
            ->willReturn($this->connectionDataMock);

        $this->pdoMock->method('prepare')
            ->willReturn($this->statementMock);

        $this->connection = new Connection(
            $this->loggerMock,
            $connectionFactoryMock,
            $this->dbConfigMock
        );

        $reflection = new \ReflectionClass(get_class($this->connection));
        $property = $reflection->getProperty('pdo');
        $property->setAccessible(true);
        $property->setValue($this->connection, $this->pdoMock);
    }

    public function testSelect()
    {
        $this->loggerMock->expects($this->once())
            ->method('debug')
            ->with('Query: some query');
        $this->statementMock->expects($this->once())
            ->method('fetchAll')
            ->willReturn(['result']);

        $this->assertSame(
            ['result'],
            $this->connection->select('some query', [])
        );
    }

    public function testSelectOne()
    {
        $this->loggerMock->expects($this->once())
            ->method('debug')
            ->with('Query: some query');
        $this->statementMock->expects($this->once())
            ->method('fetch')
            ->with(\PDO::FETCH_ASSOC)
            ->willReturn(['result']);

        $this->assertSame(
            ['result'],
            $this->connection->selectOne('some query', [])
        );
    }

    public function testListTables()
    {
        $this->loggerMock->expects($this->once())
            ->method('debug')
            ->with('Query: SHOW TABLES');
        $this->statementMock->expects($this->once())
            ->method('fetchAll')
            ->with(\PDO::FETCH_COLUMN, 0)
            ->willReturn(['result']);

        $this->assertSame(
            ['result'],
            $this->connection->listTables()
        );
    }

    /**
     * @throws PDOException
     */
    public function testGetPdo()
    {
        $this->assertSame($this->pdoMock, $this->connection->getPdo());
    }

    public function testGetPdoWithException()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Some exception');

        $this->pdoMock->expects($this->once())
            ->method('query')
            ->with('SELECT 1')
            ->willThrowException(new \Exception('Some exception'));
        $this->pdoMock->expects($this->once())
            ->method('errorInfo')
            ->willReturn([
                'HY000',
                2000,
                'Some message',
            ]);

        $this->connection->getPdo();
    }

    public function testClose()
    {
        $this->connection->close();
    }

    public function testAffectingQuery()
    {
        $bindings = [
            ':name' => 'John',
            ':age' => 2
        ];

        $this->statementMock->expects($this->exactly(2))
            ->method('bindValue')
            ->withConsecutive(
                [':name', 'John', \PDO::PARAM_STR],
                [':age', 2, \PDO::PARAM_INT]
            );
        $this->statementMock->expects($this->once())
            ->method('rowCount')
            ->willReturn(1);

        $this->assertSame(1, $this->connection->affectingQuery('SELECT 1', $bindings));
    }

    public function testQuery()
    {
        $bindings = [
            ':name' => 'John',
            ':age' => 2
        ];

        $this->statementMock->expects($this->exactly(2))
            ->method('bindValue')
            ->withConsecutive(
                [':name', 'John', \PDO::PARAM_STR],
                [':age', 2, \PDO::PARAM_INT]
            );
        $this->statementMock->expects($this->once())
            ->method('execute')
            ->willReturn(true);

        $this->connection->query('SELECT 1', $bindings);
    }

    /**
     * @param array $mergedConfig
     * @param string $tableName
     * @param string $expectedTableName
     * @dataProvider getTableNameDataProvider
     */
    public function testGetTableName(array $mergedConfig, string $tableName, string $expectedTableName)
    {
        $this->dbConfigMock->expects($this->once())
            ->method('get')
            ->willReturn($mergedConfig);

        $this->assertEquals(
            $expectedTableName,
            $this->connection->getTableName($tableName)
        );
    }

    /**
     * @return array
     */
    public function getTableNameDataProvider(): array
    {
        return [
            'empty prefix' => [
                [],
                'table',
                'table',
            ],
            'non empty prefix' => [
                [
                    'table_prefix' => 'ece_',
                ],
                'table',
                'ece_table',
            ],
        ];
    }
}
