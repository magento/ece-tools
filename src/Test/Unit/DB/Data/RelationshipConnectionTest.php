<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Test\Unit\DB\Data;

use Magento\MagentoCloud\DB\Data\RelationshipConnection;
use PHPUnit\Framework\TestCase;

/**
 * @inheritdoc
 */
class RelationshipConnectionTest extends TestCase
{
    public function testGetOptions()
    {
        $relationshipConnection = new RelationshipConnection([
            'host' => '127.0.0.1',
            'port' => '3306',
            'path' => 'dbName',
            'username' => 'user',
            'password' => '1234',
            'driver_options' => [
                'option1' => 'value1',
                'option2' => 'value2'
            ],
        ]);

        $this->assertEquals('127.0.0.1', $relationshipConnection->getHost());
        $this->assertEquals('3306', $relationshipConnection->getPort());
        $this->assertEquals('dbName', $relationshipConnection->getDbName());
        $this->assertEquals('user', $relationshipConnection->getUser());
        $this->assertEquals('1234', $relationshipConnection->getPassword());
        $this->assertEquals(
            [
                'option1' => 'value1',
                'option2' => 'value2'
            ],
            $relationshipConnection->getDriverOptions()
        );
    }

    public function testGetEmptyOptions()
    {
        $relationshipConnection = new RelationshipConnection([]);

        $this->assertEmpty($relationshipConnection->getHost());
        $this->assertEmpty($relationshipConnection->getPort());
        $this->assertEmpty($relationshipConnection->getDbName());
        $this->assertEmpty($relationshipConnection->getUser());
        $this->assertEmpty($relationshipConnection->getPassword());
        $this->assertEquals([], $relationshipConnection->getDriverOptions());
    }
}
