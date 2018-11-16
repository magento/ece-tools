<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Unit\DB\Data;

use Magento\MagentoCloud\Config\Environment;
use Magento\MagentoCloud\DB\Data\RelationshipConnectionFactory;
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
     * @var Environment|MockObject
     */
    private $environmentMock;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        $this->environmentMock = $this->createMock(Environment::class);

        $this->factory = new RelationshipConnectionFactory(
            $this->environmentMock
        );
    }

    public function testCreateMain()
    {
        $this->environmentMock->expects($this->once())
            ->method('getRelationship')
            ->with('database')
            ->willReturn([[]]);

        $this->factory->create(RelationshipConnectionFactory::CONNECTION_MAIN);
    }

    public function testCreateSlave()
    {
        $this->environmentMock->expects($this->once())
            ->method('getRelationship')
            ->with('database-slave')
            ->willReturn([[]]);

        $this->factory->create(RelationshipConnectionFactory::CONNECTION_SLAVE);
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
