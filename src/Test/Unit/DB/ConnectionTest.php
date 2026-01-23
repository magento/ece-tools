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
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\MockObject\Rule\InvocationOrder;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * @inheritdoc
 */
#[AllowMockObjectsWithoutExpectations]
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
        $this->loggerMock = $this->createMock(LoggerInterface::class);
        $this->connectionDataMock = $this->createMock(ConnectionInterface::class);

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

        # Note: setAccessible(true) is deprecated in PHP 8.5 as properties are always accessible in PHP 8.1+
        # so removed the call to setAccessible(true)

        $property->setValue($this->connection, $this->pdoMock);
    }

    /**
     * Test select method.
     *
     * @return void
     */
    public function testSelect(): void
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

    /**
     * Test selectOne method.
     *
     * @return void
     */
    public function testSelectOne(): void
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

    /**
     * Test listTables method.
     *
     * @return void
     */
    public function testListTables(): void
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
     * Test getPdo method.
     *
     * @return void
     * @throws PDOException
     */
    public function testGetPdo(): void
    {
        $this->assertSame($this->pdoMock, $this->connection->getPdo());
    }

    /**
     * Test getPdo method with exception.
     *
     * @return void
     * @throws \Exception
     */
    public function testGetPdoWithException(): void
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

    /**
     * Test close method.
     *
     * @return void
     */
    public function testClose(): void
    {
        $this->connection->close();
        $this->expectNotToPerformAssertions();
    }

    /**
     * Test affectingQuery method.
     *
     * @return void
     */
    public function testAffectingQuery(): void
    {
        $bindings = [
            ':name' => 'John',
            ':age' => 2
        ];
        $this->statementMock->expects($this->exactly(2))
            ->method('bindValue')
            ->willReturnCallback(function ($arg1, $arg2, $arg3) {
                if ($arg1 == ':name' && $arg2 == 'John' && $arg3 == \PDO::PARAM_STR) {
                    return true;
                } elseif ($arg1 == ':age' && $arg2 == 2 && $arg3 == \PDO::PARAM_INT) {
                    return true;
                }
            });

        $this->statementMock->expects($this->once())
            ->method('rowCount')
            ->willReturn(1);

        $this->assertSame(1, $this->connection->affectingQuery('SELECT 1', $bindings));
    }

    /**
     * Test query method.
     *
     * @return void
     */
    public function testQuery(): void
    {
        $bindings = [
            ':name' => 'John',
            ':age' => 2
        ];

        $this->statementMock->expects($this->exactly(2))
            ->method('bindValue')
            ->willReturnCallback(function ($arg1, $arg2, $arg3) {
                if ($arg1 == ':name' && $arg2 == 'John' && $arg3 == \PDO::PARAM_STR) {
                    return true;
                } elseif ($arg1 == ':age' && $arg2 == 2 && $arg3 == \PDO::PARAM_INT) {
                    return true;
                }
            });
        $this->statementMock->expects($this->once())
            ->method('execute')
            ->willReturn(true);

        $this->connection->query('SELECT 1', $bindings);
    }

    /**
     * Test getTableName method.
     *
     * @param array $mergedConfig
     * @param string $tableName
     * @param string $expectedTableName
     * @dataProvider getTableNameDataProvider
     * @return void
     */
    #[DataProvider('getTableNameDataProvider')]
    public function testGetTableName(
        array $mergedConfig,
        string $tableName,
        string $expectedTableName
    ): void {
        $this->dbConfigMock->expects($this->once())
            ->method('get')
            ->willReturn($mergedConfig);

        $this->assertEquals(
            $expectedTableName,
            $this->connection->getTableName($tableName)
        );
    }

    /**
     * Data provider for getTableName method.
     *
     * @return array
     */
    public static function getTableNameDataProvider(): array
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

    /**
     * Resolve invocations.
     *
     * @param InvocationOrder $matcher
     * @return int
     */
    private function resolveInvocations(InvocationOrder $matcher): int
    {
        if (method_exists($matcher, 'numberOfInvocations')) {
            // PHPUnit 10+ (including PHPUnit 12)
            return $matcher->numberOfInvocations();
        }

        if (method_exists($matcher, 'getInvocationCount')) {
            // before PHPUnit 10
            return $matcher->getInvocationCount();
        }

        $this->fail('Cannot count the number of invocations.');
    }
}
