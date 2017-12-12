<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Unit\Process\Deploy\InstallUpdate\ConfigUpdate;

use PHPUnit\Framework\TestCase;
use Magento\MagentoCloud\Config\Environment;
use Magento\MagentoCloud\Config\Deploy\Reader as ConfigReader;
use Magento\MagentoCloud\Config\Deploy\Writer as ConfigWriter;
use Psr\Log\LoggerInterface;
use Magento\MagentoCloud\Process\Deploy\InstallUpdate\ConfigUpdate\Amqp;

/**
 * @inheritdoc
 */
class AmqpTest extends TestCase
{
    /**
     * @var Environment|\PHPUnit_Framework_MockObject_MockObject
     */
    private $environmentMock;

    /**
     * @var LoggerInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $loggerMock;

    /**
     * @var ConfigWriter|\PHPUnit_Framework_MockObject_MockObject
     */
    private $configWriterMock;

    /**
     * @var ConfigReader|\PHPUnit_Framework_MockObject_MockObject
     */
    private $configReaderMock;

    /**
     * @var Amqp
     */
    private $amqp;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        $this->environmentMock = $this->createMock(Environment::class);
        $this->loggerMock = $this->getMockBuilder(LoggerInterface::class)
            ->getMockForAbstractClass();
        $this->configWriterMock = $this->createMock(ConfigWriter::class);
        $this->configReaderMock = $this->createMock(ConfigReader::class);

        $this->amqp = new Amqp(
            $this->environmentMock,
            $this->configReaderMock,
            $this->configWriterMock,
            $this->loggerMock
        );
    }

    /**
     * @return void
     */
    public function testExecuteWithoutAmqp()
    {
        $config = ['some config'];
        $this->environmentMock->expects($this->once())
            ->method('getJsonVariable')
            ->with(Environment::VAR_QUEUE_CONFIGURATION)
            ->willReturn([]);
        $this->environmentMock->expects($this->any())
            ->method('getRelationship')
            ->with($this->anything())
            ->willReturn([]);
        $this->configReaderMock->expects($this->once())
            ->method('read')
            ->willReturn($config);
        $this->configWriterMock->expects($this->once())
            ->method('write')
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
        $this->environmentMock->expects($this->once())
            ->method('getJsonVariable')
            ->with(Environment::VAR_QUEUE_CONFIGURATION)
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
            ->method('write')
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
                [[
                    'host' => 'localhost',
                    'port' => '777',
                    'username' => 'login',
                    'password' => 'pswd',
                    'vhost' => 'virtualhost'
                ]],
                [
                    'some config',
                    'queue' => [
                        'amqp' => [
                            'host' => 'localhost',
                            'port' => '777',
                            'user' => 'login',
                            'password' => 'pswd',
                            'virtualhost' => 'virtualhost',
                            'ssl' => '',
                        ]
                    ],
                ]
            ],
            [
                ['some config'],
                [],
                [[
                    'host' => 'localhost',
                    'port' => '777',
                    'username' => 'login',
                    'password' => 'pswd',
                ]],
                [
                    'some config',
                    'queue' => [
                        'amqp' => [
                            'host' => 'localhost',
                            'port' => '777',
                            'user' => 'login',
                            'password' => 'pswd',
                            'virtualhost' => '/',
                            'ssl' => '',
                        ]
                    ],
                ]
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
                            'ssl' => '1',
                        ]
                    ],
                ],
                [],
                [[
                    'host' => 'localhost',
                    'port' => '777',
                    'username' => 'login',
                    'password' => 'pswd',
                    'vhost' => 'virtualhost'
                ]],
                [
                    'some config',
                    'queue' => [
                        'amqp' => [
                            'host' => 'localhost',
                            'port' => '777',
                            'user' => 'login',
                            'password' => 'pswd',
                            'virtualhost' => 'virtualhost',
                            'ssl' => '',
                        ]
                    ],
                ]
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
                            'ssl' => '1',
                        ]
                    ],
                ],
                [
                    'amqp' => [
                        'host' => 'env-config-host',
                        'port' => 'env-config-port',
                        'user' => 'env-config-user',
                        'password' => 'env-config-password',
                    ]
                ],
                [[
                    'host' => 'localhost',
                    'port' => '777',
                    'username' => 'login',
                    'password' => 'pswd',
                    'vhost' => 'virtualhost'
                ]],
                [
                    'some config',
                    'queue' => [
                        'amqp' => [
                            'host' => 'env-config-host',
                            'port' => 'env-config-port',
                            'user' => 'env-config-user',
                            'password' => 'env-config-password',
                        ]
                    ],
                ]
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
        $this->environmentMock->expects($this->once())
            ->method('getJsonVariable')
            ->with(Environment::VAR_QUEUE_CONFIGURATION)
            ->willReturn([]);
        $this->environmentMock->expects($this->any())
            ->method('getRelationship')
            ->with($this->anything())
            ->willReturn([]);
        $this->configReaderMock->expects($this->once())
            ->method('read')
            ->willReturn($config);
        $this->configWriterMock->expects($this->once())
            ->method('write')
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
                            'ssl' => '',
                        ]
                    ],
                ],
                'expectedConfig' => ['some config',]
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
                            'ssl' => '',
                        ],
                        'someQueue' => ['some queue config']
                    ],
                ],
                'expectedConfig' => [
                    'some config',
                    'queue' => [
                        'someQueue' => ['some queue config']
                    ]
                ]
            ]
        ];
    }
}
