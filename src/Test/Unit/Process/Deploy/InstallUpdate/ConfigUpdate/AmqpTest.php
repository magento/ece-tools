<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Unit\Process\Deploy\InstallUpdate\ConfigUpdate;

use PHPUnit\Framework\TestCase;
use Magento\MagentoCloud\Config\Deploy\Reader as ConfigReader;
use Magento\MagentoCloud\Config\Deploy\Writer as ConfigWriter;
use Psr\Log\LoggerInterface;
use Magento\MagentoCloud\Process\Deploy\InstallUpdate\ConfigUpdate\Amqp;
use PHPUnit_Framework_MockObject_MockObject as Mock;
use Magento\MagentoCloud\Process\Deploy\InstallUpdate\ConfigUpdate\Amqp\Config as AmqpConfig;

/**
 * @inheritdoc
 */
class AmqpTest extends TestCase
{
    /**
     * @var Amqp
     */
    private $process;

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
     * @var AmqpConfig|Mock
     */
    private $amqpConfigMock;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        $this->loggerMock = $this->getMockForAbstractClass(LoggerInterface::class);
        $this->configWriterMock = $this->createMock(ConfigWriter::class);
        $this->configReaderMock = $this->createMock(ConfigReader::class);
        $this->amqpConfigMock = $this->createMock(AmqpConfig::class);

        $this->process = new Amqp(
            $this->configReaderMock,
            $this->configWriterMock,
            $this->loggerMock,
            $this->amqpConfigMock
        );
    }

    /**
     * @return void
     */
    public function testExecuteWithoutAmqp()
    {
        $config = ['some config'];

        $this->configReaderMock->expects($this->once())
            ->method('read')
            ->willReturn($config);
        $this->amqpConfigMock->expects($this->once())
            ->method('get')
            ->willReturn([]);
        $this->loggerMock->expects($this->never())
            ->method('info');
        $this->configWriterMock->expects($this->never())
            ->method('create')
            ->with($config);

        $this->process->execute();
    }

    /**
     * @return void
     */
    public function testExecuteAddUpdate()
    {
        $config = ['some config'];
        $amqpConfig = [
            'amqp' => [
                'host' => 'localhost',
                'port' => '777',
                'username' => 'login',
                'password' => 'pswd',
                'vhost' => 'virtualhost',
            ]
        ];
        $resultConfig = [
            'some config',
            'queue' => $amqpConfig
        ];

        $this->configReaderMock->expects($this->once())
            ->method('read')
            ->willReturn($config);
        $this->amqpConfigMock->expects($this->once())
            ->method('get')
            ->willReturn($amqpConfig);
        $this->loggerMock->expects($this->once())
            ->method('info')
            ->with('Updating env.php AMQP configuration.');
        $this->configWriterMock->expects($this->once())
            ->method('create')
            ->with($resultConfig);

        $this->process->execute();
    }

    /**
     * @return void
     */
    public function testExecuteRemoveAmqp()
    {
        $config = [
            'some config',
            'queue' => [
                'amqp' => [
                    'host' => 'localhost',
                    'port' => '777',
                    'user' => 'login',
                    'password' => 'pswd',
                    'virtualhost' => '/',
                ],
            ],
        ];
        $expectedConfig = ['some config'];

        $this->configReaderMock->expects($this->once())
            ->method('read')
            ->willReturn($config);
        $this->loggerMock->expects($this->once())
            ->method('info')
            ->with('Removing queue configuration from env.php.');
        $this->configWriterMock->expects($this->once())
            ->method('create')
            ->with($expectedConfig);

        $this->process->execute();
    }
}
