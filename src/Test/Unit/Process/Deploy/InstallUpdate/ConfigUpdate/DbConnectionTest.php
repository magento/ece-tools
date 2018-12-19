<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Unit\Process\Deploy\InstallUpdate\ConfigUpdate;

use Magento\MagentoCloud\Config\ConfigMerger;
use Magento\MagentoCloud\Config\Deploy\Reader as ConfigReader;
use Magento\MagentoCloud\Config\Deploy\Writer as ConfigWriter;
use Magento\MagentoCloud\Config\Database\MergedConfig;
use Magento\MagentoCloud\Config\Database\ResourceConfig;
use Magento\MagentoCloud\Config\Stage\DeployInterface;
use Magento\MagentoCloud\DB\Data\ConnectionInterface;
use Magento\MagentoCloud\DB\Data\RelationshipConnectionFactory;
use Magento\MagentoCloud\Process\Deploy\InstallUpdate\ConfigUpdate\DbConnection;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * @inheritdoc
 */
class DbConnectionTest extends TestCase
{
    /**
     * @var DeployInterface|MockObject
     */
    private $stageConfigMock;

    /**
     * @var LoggerInterface|MockObject
     */
    private $loggerMock;

    /**
     * @var ConfigWriter|MockObject
     */
    private $configWriterMock;

    /**
     * @var ConfigReader|MockObject
     */
    private $configReaderMock;

    /**
     * @var MergedConfig|MockObject
     */
    private $mergedConfigMock;

    /**
     * @var ResourceConfig|MockObject
     */
    private $resourceConfigMock;

    /**
     * @var RelationshipConnectionFactory|MockObject
     */
    private $connectionFactoryMock;

    /**
     * @var ConnectionInterface|MockObject
     */
    private $connectionDataMock;

    /**
     * @var DbConnection
     */
    private $process;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        $this->stageConfigMock = $this->getMockForAbstractClass(DeployInterface::class);
        $this->mergedConfigMock = $this->createMock(MergedConfig::class);
        $this->resourceConfigMock = $this->createMock(ResourceConfig::class);
        $this->configWriterMock = $this->createMock(ConfigWriter::class);
        $this->configReaderMock = $this->createMock(ConfigReader::class);
        $this->loggerMock = $this->getMockForAbstractClass(LoggerInterface::class);
        $this->connectionDataMock = $this->getMockForAbstractClass(ConnectionInterface::class);
        $this->connectionFactoryMock = $this->createMock(RelationshipConnectionFactory::class);
        $this->connectionFactoryMock->expects($this->any())
            ->method('create')
            ->willReturn($this->connectionDataMock);

        $this->process = new DbConnection(
            $this->stageConfigMock,
            $this->mergedConfigMock,
            $this->resourceConfigMock,
            $this->configWriterMock,
            $this->configReaderMock,
            new ConfigMerger(),
            $this->connectionFactoryMock,
            $this->loggerMock
        );
    }

    public function testExecute()
    {
        $this->mergedConfigMock->expects($this->once())
            ->method('get')
            ->willReturn(['connection' => [
                'default' => ['host' => 'some.host']
            ]]);
        $this->resourceConfigMock->expects($this->once())
            ->method('get')
            ->willReturn(['default_setup' => [
                'connection' => 'default'
            ]]);
        $this->loggerMock->expects($this->once())
            ->method('info')
            ->with('Updating env.php DB connection configuration.');
        $this->configReaderMock->expects($this->once())
            ->method('read')
            ->willReturn([
                'db' => [
                    'connection' => [
                        'default' => ['host' => 'custom.host']
                    ],
                ],
            ]);
        $this->configWriterMock->expects($this->once())
            ->method('create')
            ->with([
                'db' => [
                    'connection' => [
                        'default' => ['host' => 'some.host']
                    ],
                ],
                'resource' => [
                    'default_setup' => [
                        'connection' => 'default',
                    ],
                ],
            ]);
        $this->connectionDataMock->expects($this->once())
            ->method('getHost')
            ->willReturn('some.host');
        $this->stageConfigMock->expects($this->any())
            ->method('get')
            ->willReturnMap([
                [DeployInterface::VAR_MYSQL_USE_SLAVE_CONNECTION, false],
                [DeployInterface::VAR_DATABASE_CONFIGURATION, []],
            ]);
        $this->loggerMock->expects($this->never())
            ->method('warning');

        $this->process->execute();
    }

    public function testExecuteWithNotCompatibleDatabaseSettingsForSlaveConnection()
    {
        $this->mergedConfigMock->expects($this->once())
            ->method('get')
            ->willReturn(['connection' => [
                'default' => ['host' => 'some.host']
            ]]);
        $this->loggerMock->expects($this->once())
            ->method('info')
            ->with('Updating env.php DB connection configuration.');
        $this->loggerMock->expects($this->once())
            ->method('warning')
            ->with('You have changed db configuration that not compatible with default slave connection.');
        $this->stageConfigMock->expects($this->any())
            ->method('get')
            ->willReturnMap([
                [DeployInterface::VAR_MYSQL_USE_SLAVE_CONNECTION, true],
                [DeployInterface::VAR_DATABASE_CONFIGURATION, []],
            ]);
        $this->connectionDataMock->expects($this->once())
            ->method('getHost')
            ->willReturn('some.host');
        $this->mergedConfigMock->expects($this->once())
            ->method('isDbConfigurationCompatibleWithSlaveConnection')
            ->willReturn(false);

        $this->process->execute();
    }

    public function testExecuteSetSlaveConnection()
    {
        $this->mergedConfigMock->expects($this->exactly(2))
            ->method('get')
            ->willReturn([
                'connection' => ['default' => ['host' => 'some.host']],
                'slave_connection' => ['default' => ['host' => 'some_slave.host']],
            ]);
        $this->loggerMock->expects($this->exactly(2))
            ->method('info')
            ->withConsecutive(
                ['Updating env.php DB connection configuration.'],
                ['Set DB slave connection.']
            );
        $this->loggerMock->expects($this->never())
            ->method('warning');
        $this->stageConfigMock->expects($this->any())
            ->method('get')
            ->willReturnMap([
                [DeployInterface::VAR_MYSQL_USE_SLAVE_CONNECTION, true],
                [DeployInterface::VAR_DATABASE_CONFIGURATION, []],
            ]);
        $this->connectionDataMock->expects($this->once())
            ->method('getHost')
            ->willReturn('some.host');
        $this->mergedConfigMock->expects($this->once())
            ->method('isDbConfigurationCompatibleWithSlaveConnection')
            ->willReturn(true);

        $this->process->execute();
    }

    public function testExecuteSetSlaveConnectionHadNoEffect()
    {
        $this->mergedConfigMock->expects($this->exactly(2))
            ->method('get')
            ->willReturn(['connection' => ['default' => ['host' => 'some.host']]]);
        $this->loggerMock->expects($this->exactly(2))
            ->method('info')
            ->withConsecutive(
                ['Updating env.php DB connection configuration.'],
                [
                    'Enabling of the variable MYSQL_USE_SLAVE_CONNECTION had no effect because ' .
                    'slave connection is not configured on your environment.'
                ]
            );
        $this->loggerMock->expects($this->never())
            ->method('warning');
        $this->stageConfigMock->expects($this->any())
            ->method('get')
            ->willReturnMap([
                [DeployInterface::VAR_MYSQL_USE_SLAVE_CONNECTION, true],
                [DeployInterface::VAR_DATABASE_CONFIGURATION, []],
            ]);
        $this->connectionDataMock->expects($this->once())
            ->method('getHost')
            ->willReturn('some.host');
        $this->mergedConfigMock->expects($this->once())
            ->method('isDbConfigurationCompatibleWithSlaveConnection')
            ->willReturn(true);

        $this->process->execute();
    }
}
