<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Unit\Process\Deploy\InstallUpdate\ConfigUpdate;

use Magento\MagentoCloud\Config\Stage\DeployInterface;
use PHPUnit\Framework\TestCase;
use Magento\MagentoCloud\Config\Environment;
use Magento\MagentoCloud\Config\Deploy\Reader as ConfigReader;
use Magento\MagentoCloud\Config\Deploy\Writer as ConfigWriter;
use Psr\Log\LoggerInterface;
use Magento\MagentoCloud\Process\Deploy\InstallUpdate\ConfigUpdate\Amqp;
use PHPUnit_Framework_MockObject_MockObject as Mock;

/**
 * @inheritdoc
 */
class AmqpTest extends TestCase
{
    /**
     * @var Amqp
     */
    private $amqp;

    /**
     * @var Environment|Mock
     */
    private $environmentMock;

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
     * @var DeployInterface|Mock
     */
    private $stageConfigMock;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        $this->environmentMock = $this->createMock(Environment::class);
        $this->loggerMock = $this->getMockForAbstractClass(LoggerInterface::class);
        $this->configWriterMock = $this->createMock(ConfigWriter::class);
        $this->configReaderMock = $this->createMock(ConfigReader::class);
        $this->stageConfigMock = $this->getMockForAbstractClass(DeployInterface::class);

        $this->amqp = new Amqp(
            $this->environmentMock,
            $this->configReaderMock,
            $this->configWriterMock,
            $this->loggerMock,
            $this->stageConfigMock
        );
    }

    /**
     * @return void
     */
    public function testExecuteWithoutAmqp()
    {
        $config = ['some config'];
        $this->stageConfigMock->expects($this->once())
            ->method('get')
            ->with(DeployInterface::VAR_QUEUE_CONFIGURATION)
            ->willReturn([]);
        $this->environmentMock->expects($this->any())
            ->method('getRelationship')
            ->with($this->anything())
            ->willReturn([]);
        $this->configReaderMock->expects($this->once())
            ->method('read')
            ->willReturn($config);
        $this->configWriterMock->expects($this->once())
            ->method('create')
            ->with($config);
        $this->loggerMock->expects($this->never())
            ->method('info');

        $this->amqp->execute();
    }

    /**
     * @param array $config
     * @param array $envQueueConfig
     * @param array $amqpConfig
     * @param array $resultConfig
     * @return void
     * @dataProvider executeAddUpdateDataProvider
     */
    public function testExecuteAddUpdate(
        array $config,
        array $envQueueConfig,
        array $amqpConfig,
        array $resultConfig
    ) {
        $this->stageConfigMock->expects($this->once())
            ->method('get')
            ->with(DeployInterface::VAR_QUEUE_CONFIGURATION)
            ->willReturn($envQueueConfig);
        $this->environmentMock->expects($this->exactly(2))
            ->method('getRelationship')
            ->withConsecutive(
                ['rabbitmq'],
                ['mq']
            )
            ->willReturnOnConsecutiveCalls(
                [],
                $amqpConfig
            );
        $this->configReaderMock->expects($this->once())
            ->method('read')
            ->willReturn($config);
        $this->configWriterMock->expects($this->once())
            ->method('create')
            ->with($resultConfig);
        $this->loggerMock->expects($this->once())
            ->method('info')
            ->with('Updating env.php AMQP configuration.');

        $this->amqp->execute();
    }

    /**
     * @return array
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function executeAddUpdateDataProvider(): array
    {
        return [
            [
                ['some config'],
                [],
                [
                    [
                        'host' => 'localhost',
                        'port' => '777',
                        'username' => 'login',
                        'password' => 'pswd',
                        'vhost' => 'virtualhost',
                    ],
                ],
                [
                    'some config',
                    'queue' => [
                        'amqp' => [
                            'host' => 'localhost',
                            'port' => '777',
                            'user' => 'login',
                            'password' => 'pswd',
                            'virtualhost' => 'virtualhost',
                        ],
                    ],
                ],
            ],
            [
                ['some config'],
                [],
                [
                    [
                        'host' => 'localhost',
                        'port' => '777',
                        'username' => 'login',
                        'password' => 'pswd',
                    ],
                ],
                [
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
                ],
            ],
            [
                [
                    'some config',
                    'queue' => [
                        'amqp' => [
                            'host' => 'some-host',
                            'port' => '888',
                            'user' => 'mylogin',
                            'password' => 'mysqwwd',
                            'virtualhost' => 'virtualhost',
                            'someoption1' => 'some_value',
                            'someoption2' => 'some_value',
                        ],
                    ],
                ],
                [],
                [
                    [
                        'host' => 'localhost',
                        'port' => '777',
                        'username' => 'login',
                        'password' => 'pswd',
                        'vhost' => 'virtualhost',
                    ],
                ],
                [
                    'some config',
                    'queue' => [
                        'amqp' => [
                            'host' => 'localhost',
                            'port' => '777',
                            'user' => 'login',
                            'password' => 'pswd',
                            'virtualhost' => 'virtualhost',
                        ],
                    ],
                ],
            ],
            [
                [
                    'some config',
                    'queue' => [
                        'amqp' => [
                            'host' => 'some-host',
                            'port' => '888',
                            'user' => 'mylogin',
                            'password' => 'mysqwwd',
                            'virtualhost' => 'virtualhost',
                        ],
                    ],
                ],
                [
                    'amqp' => [
                        'host' => 'env-config-host',
                        'port' => 'env-config-port',
                        'user' => 'env-config-user',
                        'password' => 'env-config-password',
                    ],
                ],
                [
                    [
                        'host' => 'localhost',
                        'port' => '777',
                        'username' => 'login',
                        'password' => 'pswd',
                        'vhost' => 'virtualhost',
                    ],
                ],
                [
                    'some config',
                    'queue' => [
                        'amqp' => [
                            'host' => 'env-config-host',
                            'port' => 'env-config-port',
                            'user' => 'env-config-user',
                            'password' => 'env-config-password',
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * @param array $config
     * @param array $expectedConfig
     * @return void
     * @dataProvider executeRemoveAmqpDataProvider
     */
    public function testExecuteRemoveAmqp(array $config, array $expectedConfig)
    {
        $this->stageConfigMock->expects($this->once())
            ->method('get')
            ->with(DeployInterface::VAR_QUEUE_CONFIGURATION)
            ->willReturn([]);
        $this->environmentMock->expects($this->any())
            ->method('getRelationship')
            ->with($this->anything())
            ->willReturn([]);
        $this->configReaderMock->expects($this->once())
            ->method('read')
            ->willReturn($config);
        $this->configWriterMock->expects($this->once())
            ->method('create')
            ->with($expectedConfig);
        $this->loggerMock->expects($this->once())
            ->method('info')
            ->with('Removing AMQP configuration from env.php.');

        $this->amqp->execute();
    }

    /**
     * @return array
     */
    public function executeRemoveAmqpDataProvider(): array
    {
        return [
            [
                'config' => [
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
                ],
                'expectedConfig' => ['some config',],
            ],
            [
                'config' => [
                    'some config',
                    'queue' => [
                        'amqp' => [
                            'host' => 'localhost',
                            'port' => '777',
                            'user' => 'login',
                            'password' => 'pswd',
                            'virtualhost' => '/',
                        ],
                        'someQueue' => ['some queue config'],
                    ],
                ],
                'expectedConfig' => [
                    'some config',
                    'queue' => [
                        'someQueue' => ['some queue config'],
                    ],
                ],
            ],
        ];
    }
}
