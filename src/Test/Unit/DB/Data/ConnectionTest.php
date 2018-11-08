<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Unit\DB\Data;

use Magento\MagentoCloud\DB\Data\Connection;
use PHPUnit\Framework\TestCase;

/**
 * @inheritdoc
 */
class ConnectionTest extends TestCase
{
    public function testGetOptions()
    {
        $relationshipConnection = new Connection([
            'host' => '127.0.0.1',
            'port' => '3306',
            'dbname' => 'dbName',
            'username' => 'user',
            'password' => '1234',
        ]);

        $this->assertEquals('127.0.0.1', $relationshipConnection->getHost());
        $this->assertEquals('3306', $relationshipConnection->getPort());
        $this->assertEquals('dbName', $relationshipConnection->getDbName());
        $this->assertEquals('user', $relationshipConnection->getUser());
        $this->assertEquals('1234', $relationshipConnection->getPassword());
    }

    public function testGetOptionsWithEmptyPortAndPortInHost()
    {
        $relationshipConnection = new Connection([
            'host' => '127.0.0.1:3306',
            'port' => ''
        ]);

        $this->assertEquals('127.0.0.1', $relationshipConnection->getHost());
        $this->assertEquals('3306', $relationshipConnection->getPort());
    }

    public function testGetOptionsWithEmptyPortAndNoPortInHost()
    {
        $relationshipConnection = new Connection([
            'host' => '127.0.0.1',
            'port' => ''
        ]);

        $this->assertEquals('127.0.0.1', $relationshipConnection->getHost());
        $this->assertEquals('', $relationshipConnection->getPort());
    }

    public function testGetOptionsWithNotEmptyPortAndPortInHost()
    {
        $relationshipConnection = new Connection([
            'host' => '127.0.0.1:3306',
            'port' => '3305'
        ]);

        $this->assertEquals('127.0.0.1', $relationshipConnection->getHost());
        $this->assertEquals('3305', $relationshipConnection->getPort());
    }

    public function testGetOptionsWithEmptyPortAndSocketHost()
    {
        $relationshipConnection = new Connection([
            'host' => '/tmp/mysql.sock',
            'port' => ''
        ]);

        $this->assertEquals('/tmp/mysql.sock', $relationshipConnection->getHost());
        $this->assertEquals('', $relationshipConnection->getPort());
    }
}
