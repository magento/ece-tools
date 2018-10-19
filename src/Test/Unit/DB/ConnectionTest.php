<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Unit\DB;

use Magento\MagentoCloud\DB\Connection;
use Magento\MagentoCloud\DB\Data\ConnectionFactory;
use Magento\MagentoCloud\DB\Data\ConnectionInterface;
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
     * @var ConnectionInterface
     */
    private $connectionDataMock;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        $this->pdoMock = $this->createMock(\PDO::class);
        $this->statementMock = $this->createMock(\PDOStatement::class);
        $this->loggerMock = $this->getMockForAbstractClass(LoggerInterface::class);
        $this->connectionDataMock = $this->getMockForAbstractClass(ConnectionInterface::class);
        /** @var ConnectionFactory|MockObject $connectionFactoryMock */
        $connectionFactoryMock = $this->createMock(ConnectionFactory::class);
        $connectionFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($this->connectionDataMock);

        $this->pdoMock->expects($this->any())
            ->method('prepare')
            ->willReturn($this->statementMock);

        $this->connection = new Connection(
            $this->loggerMock,
            $connectionFactoryMock
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

    public function testGetPdo()
    {
        $this->assertSame($this->pdoMock, $this->connection->getPdo());
    }

    /**
     * @expectedException \Exception
     * @expectedExceptionMessage Some exception
     */
    public function testGetPdoWithException()
    {
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
}
