<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Unit\DB;

use Magento\MagentoCloud\DB\Data\ConnectionInterface;
use Magento\MagentoCloud\DB\Dump;
use PHPUnit\Framework\TestCase;
use PHPUnit_Framework_MockObject_MockObject as Mock;

/**
 * @inheritdoc
 */
class DumpTest extends TestCase
{
    /**
     * @var Dump
     */
    private $model;

    /**
     * @var ConnectionInterface|Mock
     */
    private $connectionDataMock;

    /**
     * Setup the test environment.
     */
    protected function setUp()
    {
        $this->connectionDataMock = $this->getMockBuilder(ConnectionInterface::class)
            ->getMockForAbstractClass();

        $this->model = new Dump($this->connectionDataMock);
    }

    /**
     * @param string $host
     * @param int $port
     * @param string $dbName
     * @param string $user
     * @param string|null $password
     * @param string $expectedCommand
     *
     * @dataProvider getCommandDataProvider
     */
    public function testGetCommand($host, $port, $dbName, $user, $password, $expectedCommand)
    {
        $this->connectionDataMock->expects($this->once())
            ->method('getHost')
            ->willReturn($host);
        $this->connectionDataMock->expects($this->once())
            ->method('getPort')
            ->willReturn($port);
        $this->connectionDataMock->expects($this->once())
            ->method('getDbName')
            ->willReturn($dbName);
        $this->connectionDataMock->expects($this->once())
            ->method('getUser')
            ->willReturn($user);
        $this->connectionDataMock->expects($this->once())
            ->method('getPassword')
            ->willReturn($password);
        $this->assertEquals($expectedCommand, $this->model->getCommand());
    }

    /**
     * Data provider for testExecute
     * @return array
     */
    public function getCommandDataProvider()
    {
        $command = 'mysqldump %s --single-transaction --no-autocommit --quick';
        return [
            [
                'localhost',
                '3306',
                'main',
                'user',
                null,
                sprintf($command, "-h 'localhost' -u 'user' -P '3306' 'main'")
            ],
            [
                'localhost',
                '3306',
                'main',
                'user',
                'pswd',
                sprintf($command, "-h 'localhost' -u 'user' -P '3306' -p'pswd' 'main'")
            ]
        ];
    }
}
