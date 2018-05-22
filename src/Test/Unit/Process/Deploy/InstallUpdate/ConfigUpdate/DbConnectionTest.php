<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Unit\Process\Deploy\InstallUpdate\ConfigUpdate;

use Magento\MagentoCloud\Config\Deploy\Reader as ConfigReader;
use Magento\MagentoCloud\Config\Deploy\Writer as ConfigWriter;
use Magento\MagentoCloud\Process\Deploy\InstallUpdate\ConfigUpdate\Db\Config;
use Magento\MagentoCloud\Process\Deploy\InstallUpdate\ConfigUpdate\DbConnection;
use PHPUnit\Framework\TestCase;
use PHPUnit_Framework_MockObject_MockObject as Mock;
use Psr\Log\LoggerInterface;

/**
 * @inheritdoc
 */
class DbConnectionTest extends TestCase
{
    /**
     * @var LoggerInterface|Mock
     */
    private $loggerMock;

    /**
     * @var ConfigWriter|Mock
     */
    private $configWriterMock;

    /**
     * @var ConfigReader|Mock
     */
    private $configReaderMock;

    /**
     * @var Config|Mock
     */
    private $dbConfigMock;

    /**
     * @var DbConnection
     */
    private $process;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        $this->dbConfigMock = $this->createMock(Config::class);
        $this->configWriterMock = $this->createMock(ConfigWriter::class);
        $this->configReaderMock = $this->createMock(ConfigReader::class);
        $this->loggerMock = $this->getMockForAbstractClass(LoggerInterface::class);

        $this->process = new DbConnection(
            $this->dbConfigMock,
            $this->configWriterMock,
            $this->configReaderMock,
            $this->loggerMock
        );
    }

    /**
     * @inheritdoc
     */
    public function testExecute()
    {
        $this->dbConfigMock->expects($this->once())
            ->method('get')
            ->willReturn(['connection' => [
                'default' => ['host' => 'some.host']
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

        $this->process->execute();
    }
}
