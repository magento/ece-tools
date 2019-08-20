<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Unit\Process\Deploy\InstallUpdate\ConfigUpdate\Amqp;

use Magento\MagentoCloud\Config\ConfigMerger;
use Magento\MagentoCloud\Config\Stage\DeployInterface;
use Magento\MagentoCloud\Process\Deploy\InstallUpdate\ConfigUpdate\Amqp\Config;
use Magento\MagentoCloud\Service\RabbitMq;
use PHPUnit\Framework\TestCase;
use PHPUnit_Framework_MockObject_MockObject as Mock;

/**
 * @inheritdoc
 */
class ConfigTest extends TestCase
{
    /**
     * @var Config
     */
    protected $config;

    /**
     * @var RabbitMq|Mock
     */
    protected $rabbitMq;

    /**
     * @var DeployInterface|Mock
     */
    private $stageConfigMock;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        $this->rabbitMq = $this->createMock(RabbitMq::class);
        $this->stageConfigMock = $this->getMockForAbstractClass(DeployInterface::class);

        $this->config = new Config(
            $this->rabbitMq,
            $this->stageConfigMock,
            new ConfigMerger()
        );
    }

    /**
     * @param array $customQueueConfig
     * @param array $amqpServiceConfig
     * @param array $expectedQueueConfig
     * @dataProvider getDataProvider
     * @return void
     */
    public function testGet(
        $customQueueConfig,
        $amqpServiceConfig,
        $expectedQueueConfig
    ) {
        $this->stageConfigMock->expects($this->once())
            ->method('get')
            ->with(DeployInterface::VAR_QUEUE_CONFIGURATION)
            ->willReturn($customQueueConfig);
        $this->rabbitMq->expects($this->once())
            ->method('getConfiguration')
            ->willReturn($amqpServiceConfig);

        $this->assertEquals($expectedQueueConfig, $this->config->get());
    }

    /**
     * @return array
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function getDataProvider(): array
    {
        return [
            'queue configuration does not exist' => [
                'customQueueConfig' => [],
                'amqpServiceConfig' => [],
                'expectedQueueConfig' => [],
            ],
            'only custom queue configuration exists' => [
                'customQueueConfig' => [
                    'amqp' => [
                        'host' => 'custom_host',
                        'port' => 3333,
                        'user' => 'custom_user',
                        'password' => 'custom_password',
                        'virtualhost' => 'custom_vhost',
                    ],
                ],
                'amqpServiceConfig' => [],
                'expectedQueueConfig' => [
                    'amqp' => [
                        'host' => 'custom_host',
                        'port' => 3333,
                        'user' => 'custom_user',
                        'password' => 'custom_password',
                        'virtualhost' => 'custom_vhost',
                    ],
                ],
            ],
            'custom and relationship queue configurations exists without merge' => [
                'customQueueConfig' => [
                    'amqp' => [
                        'host' => 'custom_host',
                        'port' => 3333,
                        'user' => 'custom_user',
                        'password' => 'custom_password',
                        'virtualhost' => 'custom_vhost',
                    ],
                ],
                'amqpServiceConfig' => [
                    'host' => 'localhost',
                    'port' => 5538,
                    'username' => 'johndoe',
                    'password' => 'qwerty',
                    'vhost' => '/'
                ],
                'expectedQueueConfig' => [
                    'amqp' => [
                        'host' => 'custom_host',
                        'port' => 3333,
                        'user' => 'custom_user',
                        'password' => 'custom_password',
                        'virtualhost' => 'custom_vhost',
                    ]
                ],
            ],
            'custom and relationship queue configurations exists with merge' => [
                'customQueueConfig' => [
                    'amqp' => [
                        'user' => 'custom_user',
                        'password' => 'custom_password',
                        'virtualhost' => 'custom_vhost',
                    ],
                    '_merge' => true,
                ],
                'amqpServiceConfig' => [
                    'host' => 'localhost',
                    'port' => 5538,
                    'username' => 'johndoe',
                    'password' => 'qwerty',
                    'vhost' => '/'
                ],
                'expectedQueueConfig' => [
                    'amqp' => [
                        'host' => 'localhost',
                        'port' => 5538,
                        'user' => 'custom_user',
                        'password' => 'custom_password',
                        'virtualhost' => 'custom_vhost',
                    ]
                ],
            ],
            'only relationships queue configuration exists' => [
                'customQueueConfig' => [],
                'amqpServiceConfig' => [
                    'host' => 'localhost',
                    'port' => 5538,
                    'username' => 'johndoe',
                    'password' => 'qwerty',
                    'vhost' => '/'
                ],
                'expectedQueueConfig' => [
                    'amqp' => [
                        'host' => 'localhost',
                        'port' => 5538,
                        'user' => 'johndoe',
                        'password' => 'qwerty',
                        'virtualhost' => '/',
                    ]
                ],
            ],
        ];
    }
}
