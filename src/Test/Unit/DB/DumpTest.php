<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Test\Unit\DB;

use Magento\MagentoCloud\DB\Data\ConnectionFactory;
use Magento\MagentoCloud\DB\Data\ConnectionInterface;
use Magento\MagentoCloud\DB\Dump;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @inheritdoc
 */
#[AllowMockObjectsWithoutExpectations]
class DumpTest extends TestCase
{
    /**
     * @var Dump
     */
    private $model;

    /**
     * @var ConnectionInterface|MockObject
     */
    private $connectionDataMock;

    /**
     * @var ConnectionFactory|MockObject
     */
    private $connectionFactoryMock;

    /**
     * Setup the test environment.
     */
    protected function setUp(): void
    {
        $this->connectionDataMock = $this->createMock(ConnectionInterface::class);
        $this->connectionFactoryMock = $this->createMock(ConnectionFactory::class);
        $this->connectionFactoryMock->expects($this->any())
            ->method('create')
            ->willReturn($this->connectionDataMock);

        $this->model = new Dump();
    }

    /**
     * Test getCommand method.
     *
     * @param string $host
     * @param int $port
     * @param string $dbName
     * @param string $user
     * @param string|null $password
     * @param string $expectedCommand
     * @dataProvider getCommandDataProvider
     * @return void
     */
    #[DataProvider('getCommandDataProvider')]
    public function testGetCommand(
        string $host,
        string $port,
        string $dbName,
        string $user,
        string|null $password,
        string $expectedCommand
    ): void {
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
        $this->assertEquals($expectedCommand, $this->model->getCommand($this->connectionDataMock));
    }

    /**
     * Data provider for getCommand method.
     *
     * @return array
     */
    public static function getCommandDataProvider(): array
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
