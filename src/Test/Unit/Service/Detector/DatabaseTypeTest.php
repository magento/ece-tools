<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Test\Unit\Service\Detector;

use Magento\MagentoCloud\DB\ConnectionInterface;
use Magento\MagentoCloud\Service\Detector\DatabaseType;
use Magento\MagentoCloud\Service\ServiceInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @inheritDoc
 */
class DatabaseTypeTest extends TestCase
{
    /**
     * @var DatabaseType
     */
    private $databaseType;

    /**
     * @var ConnectionInterface|MockObject
     */
    private $connectionMock;

    /**
     * @throws \ReflectionException
     * @inheritDoc
     */
    protected function setUp(): void
    {
        $this->connectionMock = $this->getMockForAbstractClass(ConnectionInterface::class);

        $this->databaseType = new DatabaseType($this->connectionMock);
    }

    /**
     * @param array $variables
     * @param string $expectedService
     *
     * @dataProvider getServiceNameDataProvider
     */
    public function testGetServiceName(array $variables, string $expectedService): void
    {
        $this->connectionMock->expects($this->once())
            ->method('select')
            ->with('SHOW VARIABLES LIKE "%version%"')
            ->willReturn($variables);

        $this->assertEquals($expectedService, $this->databaseType->getServiceName());
    }

    /**
     * @return array
     */
    public function getServiceNameDataProvider(): array
    {
        return [
            [
                [],
                ServiceInterface::NAME_DB_MYSQL
            ],
            [
                [
                    ['Variable_name' => 'aurora_version', 'Value' => '2.07.2'],
                    ['Variable_name' => 'version', 'Value' => '5.7.12'],
                ],
                ServiceInterface::NAME_DB_AURORA
            ],
            [
                [
                    ['Variable_name' => 'version', 'Value' => '10.4.13-MariaDB'],
                ],
                ServiceInterface::NAME_DB_MARIA
            ],
            [
                [
                    ['Variable_name' => 'version', 'Value' => '5.7.12'],
                ],
                ServiceInterface::NAME_DB_MYSQL
            ]
        ];
    }

    public function testGetServiceNameWithException(): void
    {
        $this->connectionMock->expects($this->once())
            ->method('select')
            ->with('SHOW VARIABLES LIKE "%version%"')
            ->willThrowException(new \PDOException('connection error'));

        $this->assertEquals(ServiceInterface::NAME_DB_MYSQL, $this->databaseType->getServiceName());
    }
}
