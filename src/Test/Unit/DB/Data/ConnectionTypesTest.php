<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Test\Unit\DB\Data;

use Magento\MagentoCloud\Config\Environment;
use Magento\MagentoCloud\DB\Data\ConnectionTypes;
use Magento\MagentoCloud\Service\Database;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @inheritdoc
 */
class ConnectionTypesTest extends TestCase
{

    /**
     * @var Database
     */
    private $connectionType;

    /**
     * @var Environment|MockObject
     */
    private $environmentMock;

    /**
     * @inheritdoc
     */
    public function setUp(): void
    {
        $this->environmentMock = $this->createMock(Environment::class);

        $this->connectionType = new ConnectionTypes(
            $this->environmentMock
        );
    }

    public function testGetConfiguration(): void
    {
        $this->environmentMock->expects($this->once())
            ->method('getRelationship')
            ->with(ConnectionTypes::RELATIONSHIP_KEY)
            ->willReturn([
                [
                    'host' => '127.0.0.1',
                    'port' => '3306',
                ]
            ]);

        $this->assertSame(
            [
                'host' => '127.0.0.1',
                'port' => '3306',
            ],
            $this->connectionType->getConfiguration()
        );
    }

    public function testGetSlaveConfiguration(): void
    {
        $this->environmentMock->expects($this->once())
            ->method('getRelationship')
            ->with(ConnectionTypes::RELATIONSHIP_SLAVE_KEY)
            ->willReturn([
                [
                    'host' => '127.0.0.1',
                    'port' => '3304',
                ]
            ]);

        $this->assertSame(
            [
                'host' => '127.0.0.1',
                'port' => '3304',
            ],
            $this->connectionType->getSlaveConfiguration()
        );
    }

    public function testGetQuoteConfiguration(): void
    {
        $this->environmentMock->expects($this->once())
            ->method('getRelationship')
            ->with(ConnectionTypes::RELATIONSHIP_QUOTE_KEY)
            ->willReturn([
                [
                    'host' => '127.0.0.1',
                    'port' => '3307',
                ]
            ]);

        $this->assertSame(
            [
                'host' => '127.0.0.1',
                'port' => '3307',
            ],
            $this->connectionType->getQuoteConfiguration()
        );
    }

    public function testGetQuoteSlaveConfiguration(): void
    {
        $this->environmentMock->expects($this->once())
            ->method('getRelationship')
            ->with(ConnectionTypes::RELATIONSHIP_QUOTE_SLAVE_KEY)
            ->willReturn([
                [
                    'host' => '127.0.0.1',
                    'port' => '3308',
                ]
            ]);

        $this->assertSame(
            [
                'host' => '127.0.0.1',
                'port' => '3308',
            ],
            $this->connectionType->getQuoteSlaveConfiguration()
        );
    }

    public function testGetSalesConfiguration(): void
    {
        $this->environmentMock->expects($this->once())
            ->method('getRelationship')
            ->with(ConnectionTypes::RELATIONSHIP_SALES_KEY)
            ->willReturn([
                [
                    'host' => '127.0.0.1',
                    'port' => '3309',
                ]
            ]);

        $this->assertSame(
            [
                'host' => '127.0.0.1',
                'port' => '3309',
            ],
            $this->connectionType->getSalesConfiguration()
        );
    }

    public function testGetSaleSlaveConfiguration(): void
    {
        $this->environmentMock->expects($this->once())
            ->method('getRelationship')
            ->with(ConnectionTypes::RELATIONSHIP_SALES_SLAVE_KEY)
            ->willReturn([
                [
                    'host' => '127.0.0.1',
                    'port' => '3310',
                ]
            ]);

        $this->assertSame(
            [
                'host' => '127.0.0.1',
                'port' => '3310',
            ],
            $this->connectionType->getSalesSlaveConfiguration()
        );
    }
}
