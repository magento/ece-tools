<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Test\Unit\DB\Data;

use Magento\MagentoCloud\DB\Data\RelationshipConnectionFactory;
use Magento\MagentoCloud\Service\Database;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @inheritdoc
 */
class RelationshipConnectionFactoryTest extends TestCase
{
    /**
     * @var RelationshipConnectionFactory
     */
    private $factory;

    /**
     * @var Database|MockObject
     */
    private $databaseMock;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        $this->databaseMock = $this->createMock(Database::class);

        $this->factory = new RelationshipConnectionFactory(
            $this->databaseMock
        );
    }

    public function testCreateMain()
    {
        $this->databaseMock->expects($this->once())
            ->method('getConfiguration')
            ->willReturn([]);

        $this->factory->create(RelationshipConnectionFactory::CONNECTION_MAIN);
    }

    public function testCreateSlave()
    {
        $this->databaseMock->expects($this->once())
            ->method('getSlaveConfiguration')
            ->willReturn([]);

        $this->factory->create(RelationshipConnectionFactory::CONNECTION_SLAVE);
    }

    public function testCreateWithException()
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Connection with type dummy doesn\'t exist');

        $this->factory->create('dummy');
    }
}
