<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Test\Unit\Step\Deploy\SplitDbConnection;

use Magento\MagentoCloud\Config\ConfigException;
use Magento\MagentoCloud\Config\Database\DbConfig;
use Magento\MagentoCloud\Config\Magento\Env\ReaderInterface as ConfigReader;
use Magento\MagentoCloud\Config\Magento\Env\WriterInterface as ConfigWriter;
use Magento\MagentoCloud\Config\Stage\DeployInterface;
use Magento\MagentoCloud\Filesystem\FileSystemException;
use Magento\MagentoCloud\Step\Deploy\SplitDbConnection\SlaveConnection;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use ReflectionException;

/**
 * @inheritDoc
 */
class SlaveConnectionTest extends TestCase
{
    /**
     * @var SlaveConnection
     */
    private $slaveConnection;
    /**
     * @var DeployInterface|MockObject
     */
    private $stageConfigMock;

    /**
     * @var DbConfig|MockObject
     */
    private $dbConfigMock;

    /**
     * @var LoggerInterface|MockObject
     */
    private $loggerMock;

    /**
     * @var ConfigReader|MockObject
     */
    private $configReaderMock;

    /**
     * @var ConfigWriter|MockObject
     */
    private $configWriterMock;

    /**
     * @throws ReflectionException
     */
    protected function setUp(): void
    {
        $this->stageConfigMock = $this->getMockForAbstractClass(DeployInterface::class);
        $this->dbConfigMock = $this->createMock(DbConfig::class);
        $this->loggerMock = $this->getMockForAbstractClass(LoggerInterface::class);
        $this->configReaderMock = $this->createMock(ConfigReader::class);
        $this->configWriterMock = $this->createMock(ConfigWriter::class);

        $this->slaveConnection = new SlaveConnection(
            $this->stageConfigMock,
            $this->dbConfigMock,
            $this->loggerMock,
            $this->configReaderMock,
            $this->configWriterMock
        );
    }

    /**
     * MYSQL_USE_SLAVE_CONNECTION not set
     */
    public function testUpdateVarMysqlUseSlaveConnectionIsFalse()
    {
        $this->stageConfigMock->expects($this->once())
            ->method('get')
            ->with(DeployInterface::VAR_MYSQL_USE_SLAVE_CONNECTION)
            ->willReturn(false);
        $this->loggerMock->expects($this->never())
            ->method('warning');
        $this->loggerMock->expects($this->never())
            ->method('info');
        $this->configWriterMock->expects($this->never())
            ->method('create');

        $this->slaveConnection->update();
    }

    /**
     * No split connections were established in Magento
     *
     * @param array $dbConfig
     * @throws ConfigException
     * @throws FileSystemException
     * @dataProvider dataProviderUpdateWithoutSplitConnection
     */
    public function testUpdateWithoutSplitConnection(array $dbConfig)
    {
        $this->stageConfigMock->expects($this->once())
            ->method('get')
            ->with(DeployInterface::VAR_MYSQL_USE_SLAVE_CONNECTION)
            ->willReturn(true);
        $this->configReaderMock->expects($this->once())
            ->method('read')
            ->willReturn([
                'db' => [
                    'connection' => [
                        'default' => [],
                        'indexer' => [],
                    ]
                ]
            ]);
        $this->dbConfigMock->expects($this->once())
            ->method('get')
            ->willReturn($dbConfig);
        $this->loggerMock->expects($this->never())
            ->method('warning');
        $this->loggerMock->expects($this->never())
            ->method('info');
        $this->configWriterMock->expects($this->never())
            ->method('create');

        $this->slaveConnection->update();
    }

    /**
     * @return array
     */
    public function dataProviderUpdateWithoutSplitConnection(): array
    {
        return [
            [
                [
                    'connection' => [
                        'default' => [],
                        'indexer' => [],
                    ]
                ],
            ],
            [
                [
                    'connection' => [
                        'default' => [],
                        'indexer' => [],
                        'checkout' => [],
                        'sales' => [],
                    ]
                ],
            ],
        ];
    }

    /**
     * Split slave connections not available in environment
     */
    public function testUpdateSlaveConnectionsNotAvailable()
    {
        $mageConfig = [
            'db' => [
                'connection' => [
                    'default' => [],
                    'indexer' => [],
                    'checkout' => [],
                    'sales' => [],
                ]
            ]
        ];
        $this->stageConfigMock->expects($this->once())
            ->method('get')
            ->with(DeployInterface::VAR_MYSQL_USE_SLAVE_CONNECTION)
            ->willReturn(true);
        $this->configReaderMock->expects($this->once())
            ->method('read')
            ->willReturn($mageConfig);
        $this->dbConfigMock->expects($this->once())
            ->method('get')
            ->willReturn([
                'connection' => [
                    'default' => [],
                    'indexer' => [],
                ],
                'slave_connection' => [
                    'default' => [],
                ]
            ]);
        $this->loggerMock->expects($this->exactly(2))
            ->method('warning')
            ->withConsecutive(
                [
                    'Slave connection for \'checkout\' connection not set.'
                    . ' The `relationships` configuration in the .magento.app.yaml file'
                    . ' is missing the configuration for this slave connection'
                ],
                [
                    'Slave connection for \'sales\' connection not set.'
                    . ' The `relationships` configuration in the .magento.app.yaml file'
                    . ' is missing the configuration for this slave connection'
                ]
            );
        $this->configWriterMock->create($mageConfig);
        $this->slaveConnection->update();
    }

    /**
     * Split slave connections available in environment
     */
    public function testUpdateWithSlaveConnections()
    {
        $this->stageConfigMock->expects($this->once())
            ->method('get')
            ->with(DeployInterface::VAR_MYSQL_USE_SLAVE_CONNECTION)
            ->willReturn(true);
        $this->configReaderMock->expects($this->once())
            ->method('read')
            ->willReturn([
                'db' => [
                    'connection' => [
                        'default' => [],
                        'indexer' => [],
                        'checkout' => [],
                        'sales' => [],
                    ]
                ]
            ]);
        $this->dbConfigMock->expects($this->once())
            ->method('get')
            ->willReturn([
                'connection' => [
                    'default' => [],
                    'indexer' => [],
                    'checkout' => [],
                    'sales' => [],
                ],
                'slave_connection' => [
                    'default' => [],
                    'checkout' => [],
                    'sales' => [],
                ]
            ]);
        $this->loggerMock->expects($this->exactly(2))
            ->method('info')
            ->withConsecutive(
                ['Slave connection for \'checkout\' connection was set'],
                ['Slave connection for \'sales\' connection was set']
            );

        $this->configWriterMock->create([
            'db' => [
                'connection' => [
                    'default' => [],
                    'indexer' => [],
                    'checkout' => [],
                    'sales' => [],
                ],
                'slave_connection' => [
                    'default' => [],
                    'checkout' => [],
                    'sales' => [],
                ]
            ]
        ]);

        $this->slaveConnection->update();
    }
}
