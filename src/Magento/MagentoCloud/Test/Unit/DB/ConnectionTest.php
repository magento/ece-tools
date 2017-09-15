<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Unit\DB;

use Magento\MagentoCloud\DB\Connection;
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
     * @var \PDO|\PHPUnit_Framework_MockObject_MockObject
     */
    private $pdoMock;

    /**
     * @var \PDOStatement|\PHPUnit_Framework_MockObject_MockObject
     */
    private $statementMock;

    /**
     * @var LoggerInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $loggerMock;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        $this->markTestSkipped();

        $this->pdoMock = $this->getMockBuilder(\PDO::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->loggerMock = $this->getMockBuilder(LoggerInterface::class)
            ->getMockForAbstractClass();
        $this->statementMock = $this->getMockBuilder(\PDOStatement::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->pdoMock->expects($this->any())
            ->method('prepare')
            ->willReturn($this->statementMock);

        $this->connection = new Connection(
            $this->pdoMock,
            $this->loggerMock
        );
    }

    public function testSelect()
    {
        $this->loggerMock->expects($this->once())
            ->method('info')
            ->with('Query: some query');
        $this->statementMock->expects($this->once())
            ->method('fetchAll')
            ->willReturn(['result']);

        $this->assertSame(
            ['result'],
            $this->connection->select('some query', [])
        );
    }

    public function testListTables()
    {
        $this->loggerMock->expects($this->once())
            ->method('info')
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
}
