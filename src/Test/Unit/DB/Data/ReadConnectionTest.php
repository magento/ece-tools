<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Unit\DB\Data;

use Magento\MagentoCloud\Config\Environment;
use Magento\MagentoCloud\DB\Data\ReadConnection;
use PHPUnit\Framework\TestCase;
use PHPUnit_Framework_MockObject_MockObject as Mock;

/**
 * @inheritdoc
 */
class ConnectionTest extends TestCase
{
    /**
     * @var Environment|Mock
     */
    private $environmentMock;

    /**
     * @var ReadConnection
     */
    private $model;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        $this->environmentMock = $this->getMockBuilder(Environment::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->model = new ReadConnection($this->environmentMock);
    }

    /**
     * @param string $environmentHost
     * @param string $expectedResult
     *
     * @dataProvider getHostDataProvider
     */
    public function testGetHost($environmentHost, $expectedResult)
    {
        $this->environmentMock->expects($this->any())
            ->method('getDbHost')
            ->willReturn($environmentHost);

        $this->assertEquals($expectedResult, $this->model->getHost());
    }

    /**
     * @param string $environmentHost
     * @param string $expectedResult
     *
     * @dataProvider getPortDataProvider
     */
    public function testGetPort($environmentHost, $expectedResult)
    {
        $this->environmentMock->expects($this->once())
            ->method('getDbHost')
            ->willReturn($environmentHost);

        $this->assertEquals($expectedResult, $this->model->getPort());
    }

    public function testGetDbName()
    {
        $dbName = 'main';
        $this->environmentMock->expects($this->once())
            ->method('getDbName')
            ->willReturn($dbName);

        $this->assertEquals($dbName, $this->model->getDbName());
    }

    public function testGetUser()
    {
        $user = 'user_name';
        $this->environmentMock->expects($this->once())
            ->method('getDbUser')
            ->willReturn($user);

        $this->assertEquals($user, $this->model->getUser());
    }

    public function testGetPassword()
    {
        $pswd = '123123q#';
        $this->environmentMock->expects($this->once())
            ->method('getDbPassword')
            ->willReturn($pswd);

        $this->assertEquals($pswd, $this->model->getPassword());
    }

    /**
     * Data provider for testGetHost
     *
     * @return array
     */
    public function getHostDataProvider()
    {
        return [
            ['database.internal', 'database.internal'],
            ['production_db_name', '127.0.0.1']
        ];
    }

    /**
     * Data provider for testGetPort
     *
     * @return array
     */
    public function getPortDataProvider()
    {
        return [
            ['database.internal', 3306],
            ['production_db_name', 3304]
        ];
    }
}
