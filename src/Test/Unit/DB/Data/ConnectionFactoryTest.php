<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Test\Unit\DB\Data;

use Magento\MagentoCloud\Config\Database\DbConfig;
use Magento\MagentoCloud\DB\Data\ConnectionFactory;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @inheritdoc
 */
class ConnectionFactoryTest extends TestCase
{
    /**
     * @var ConnectionFactory
     */
    private $factory;

    /**
     * @var DbConfig|MockObject
     */
    private $dbConfigMock;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->dbConfigMock = $this->createMock(DbConfig::class);

        $this->factory = new ConnectionFactory($this->dbConfigMock);
    }

    public function testCreateMain()
    {
        $this->dbConfigMock->expects($this->once())
            ->method('get')
            ->willReturn(['connection' => ['default' => ['test' => 'test']]]);

        $this->factory->create(ConnectionFactory::CONNECTION_MAIN);
    }

    public function testCreateSlave()
    {
        $this->dbConfigMock->expects($this->once())
            ->method('get')
            ->willReturn(['slave_connection' => ['default' => ['test' => 'test']]]);

        $this->factory->create(ConnectionFactory::CONNECTION_SLAVE);
    }

    public function testCreateSlaveAsMain()
    {
        $this->dbConfigMock->expects($this->once())
            ->method('get')
            ->willReturn(['connection' => ['default' => ['test' => 'test']]]);

        $this->factory->create(ConnectionFactory::CONNECTION_SLAVE);
    }

    public function testCreateWithException()
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Connection with type dummy does not exist');
        $this->factory->create('dummy');
    }
}
