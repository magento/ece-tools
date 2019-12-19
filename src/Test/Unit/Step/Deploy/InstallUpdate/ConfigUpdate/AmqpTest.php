<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Test\Unit\Step\Deploy\InstallUpdate\ConfigUpdate;

use PHPUnit\Framework\TestCase;
use Magento\MagentoCloud\Config\Magento\Env\ReaderInterface as ConfigReader;
use Magento\MagentoCloud\Config\Magento\Env\WriterInterface as ConfigWriter;
use Psr\Log\LoggerInterface;
use Magento\MagentoCloud\Step\Deploy\InstallUpdate\ConfigUpdate\Amqp;
use PHPUnit_Framework_MockObject_MockObject as Mock;
use Magento\MagentoCloud\Step\Deploy\InstallUpdate\ConfigUpdate\Amqp\Config as AmqpConfig;

/**
 * @inheritdoc
 */
class AmqpTest extends TestCase
{
    /**
     * @var Amqp
     */
    private $step;

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

        $this->step = new Amqp(
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

        $this->step->execute();
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

        $this->step->execute();
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

        $this->step->execute();
    }
}
