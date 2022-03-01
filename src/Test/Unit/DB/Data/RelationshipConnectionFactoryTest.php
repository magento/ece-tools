<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Test\Unit\DB\Data;

use Magento\MagentoCloud\DB\Data\ConnectionTypes;
use Magento\MagentoCloud\DB\Data\RelationshipConnection;
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
     * @var ConnectionTypes|MockObject
     */
    private $connectionTypeMock;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->connectionTypeMock = $this->createMock(ConnectionTypes::class);

        $this->factory = new RelationshipConnectionFactory(
            $this->connectionTypeMock
        );
    }

    /**
     * @param string $method
     * @param string $connectionType
     * @dataProvider dataProvider
     */
    public function testCreate(string $method, string $connectionType)
    {
        $this->connectionTypeMock->expects($this->once())
            ->method($method)
            ->willReturn([]);

        $this->assertInstanceOf(
            RelationshipConnection::class,
            $this->factory->create($connectionType)
        );
    }

    /**
     * Data provider for testCreate
     */
    public function dataProvider(): array
    {
        return [
            [
                'method' => 'getConfiguration',
                'connectionType' => RelationshipConnectionFactory::CONNECTION_MAIN,
            ],
            [
                'method' => 'getSlaveConfiguration',
                'connectionType' => RelationshipConnectionFactory::CONNECTION_SLAVE,
            ],
            [
                'method' => 'getQuoteConfiguration',
                'connectionType' => RelationshipConnectionFactory::CONNECTION_QUOTE_MAIN,
            ],
            [
                'method' => 'getQuoteSlaveConfiguration',
                'connectionType' => RelationshipConnectionFactory::CONNECTION_QUOTE_SLAVE,
            ],
            [
                'method' => 'getSalesConfiguration',
                'connectionType' => RelationshipConnectionFactory::CONNECTION_SALES_MAIN,
            ],
            [
                'method' => 'getSalesSlaveConfiguration',
                'connectionType' => RelationshipConnectionFactory::CONNECTION_SALES_SLAVE,
            ]
        ];
    }

    public function testCreateWithException()
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Connection with type dummy does not exist');

        $this->factory->create('dummy');
    }
}
