<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Unit\DB\Data;

use Magento\MagentoCloud\Config\Database\MergedConfig;
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
     * @var MergedConfig|MockObject
     */
    private $mergedConfigMock;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        $this->mergedConfigMock = $this->createMock(MergedConfig::class);

        $this->factory = new ConnectionFactory($this->mergedConfigMock);
    }

    public function testCreateMain()
    {
        $this->mergedConfigMock->expects($this->once())
            ->method('get')
            ->willReturn(['connection' => ['default' => ['test' => 'test']]]);

        $this->factory->create(ConnectionFactory::CONNECTION_MAIN);
    }

    public function testCreateSlave()
    {
        $this->mergedConfigMock->expects($this->once())
            ->method('get')
            ->willReturn(['slave_connection' => ['default' => ['test' => 'test']]]);

        $this->factory->create(ConnectionFactory::CONNECTION_SLAVE);
    }

    public function testCreateSlaveAsMain()
    {
        $this->mergedConfigMock->expects($this->exactly(2))
            ->method('get')
            ->willReturn(['connection' => ['default' => ['test' => 'test']]]);

        $this->factory->create(ConnectionFactory::CONNECTION_SLAVE);
    }

    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessage Connection with type dummy doesn't exist
     */
    public function testCreateWithException()
    {
        $this->factory->create('dummy');
    }
}
