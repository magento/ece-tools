<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Test\Unit\Service;

use Magento\MagentoCloud\Config\Environment;
use Magento\MagentoCloud\Service\Database;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @inheritdoc
 */
class DatabaseTest extends TestCase
{

    /**
     * @var Database|MockObject
     */
    private $database;

    /**
     * @var Environment|MockObject
     */
    private $environmentMock;

    /**
     * @inheritdoc
     */
    public function setUp()
    {
        $this->environmentMock = $this->createMock(Environment::class);

        $this->database = new Database(
            $this->environmentMock
        );
    }

    public function testGetConfiguration()
    {
        $this->environmentMock->expects($this->once())
            ->method('getRelationship')
            ->with(Database::RELATIONSHIP_KEY)
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
            $this->database->getConfiguration()
        );
    }

    public function testGetSlaveConfiguration()
    {
        $this->environmentMock->expects($this->once())
            ->method('getRelationship')
            ->with(Database::RELATIONSHIP_SLAVE_KEY)
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
            $this->database->getSlaveConfiguration()
        );
    }

    public function testGetQuoteConfiguration()
    {
        $this->environmentMock->expects($this->once())
            ->method('getRelationship')
            ->with(Database::RELATIONSHIP_QUOTE_KEY)
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
            $this->database->getQuoteConfiguration()
        );
    }

    public function testGetQuoteSlaveConfiguration()
    {
        $this->environmentMock->expects($this->once())
            ->method('getRelationship')
            ->with(Database::RELATIONSHIP_QUOTE_SLAVE_KEY)
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
            $this->database->getQuoteSlaveConfiguration()
        );
    }

    public function testGetSalesConfiguration()
    {
        $this->environmentMock->expects($this->once())
            ->method('getRelationship')
            ->with(Database::RELATIONSHIP_SALES_KEY)
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
            $this->database->getSalesConfiguration()
        );
    }

    public function testGetSaleSlaveConfiguration()
    {
        $this->environmentMock->expects($this->once())
            ->method('getRelationship')
            ->with(Database::RELATIONSHIP_SALES_SLAVE_KEY)
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
            $this->database->getSalesSlaveConfiguration()
        );
    }

    public function testGetVersion()
    {
        $this->environmentMock->expects($this->once())
            ->method('getRelationship')
            ->with(Database::RELATIONSHIP_KEY)
            ->willReturn([
                [
                    'host' => '127.0.0.1',
                    'port' => '3306',
                    'type' => 'mysql:10.2',
                ]
            ]);

        $this->assertEquals('10.2', $this->database->getVersion());
    }

    public function testGetVersionNotConfigured()
    {
        $this->environmentMock->expects($this->once())
            ->method('getRelationship')
            ->with(Database::RELATIONSHIP_KEY)
            ->willReturn([]);

        $this->assertEquals('0', $this->database->getVersion());
    }
}
