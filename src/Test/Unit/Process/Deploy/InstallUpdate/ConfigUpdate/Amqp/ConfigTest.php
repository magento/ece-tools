<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Unit\Process\Deploy\InstallUpdate\ConfigUpdate\Amqp;

use Magento\MagentoCloud\Config\ConfigMerger;
use Magento\MagentoCloud\Config\Environment;
use Magento\MagentoCloud\Config\Stage\DeployInterface;
use Magento\MagentoCloud\Process\Deploy\InstallUpdate\ConfigUpdate\Amqp\Config;
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
     * @var Environment|Mock
     */
    protected $environmentMock;

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
        $this->stageConfigMock = $this->getMockForAbstractClass(DeployInterface::class);

        $this->config = new Config(
            $this->environmentMock,
            $this->stageConfigMock,
            new ConfigMerger()
        );
    }

    /**
     * @param array $customQueueConfig
     * @param array $magentoRelationShipsQueueConfig
     * @param array $expectedQueueConfig
     * @dataProvider getDataProvider
     * @return void
     */
    public function testGet(
        $customQueueConfig,
        $magentoRelationShipsQueueConfig,
        $expectedQueueConfig
    ) {
        $this->stageConfigMock->expects($this->once())
            ->method('get')
            ->with(DeployInterface::VAR_QUEUE_CONFIGURATION)
            ->willReturn($customQueueConfig);
        $this->environmentMock->expects($this->exactly(3))
            ->method('getRelationship')
            ->withConsecutive(['rabbitmq'], ['mq'], ['amqp'])
            ->willReturnOnConsecutiveCalls([], [], $magentoRelationShipsQueueConfig);

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
                'magentoRelationShipsQueueConfig' => [],
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
                'magentoRelationShipsQueueConfig' => [],
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
                'magentoRelationShipsQueueConfig' => [
                    0 => [
                        'host' => 'localhost',
                        'port' => 5538,
                        'username' => 'johndoe',
                        'password' => 'qwerty',
                        'vhost' => '/'
                    ]
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
                'magentoRelationShipsQueueConfig' => [
                    0 => [
                        'host' => 'localhost',
                        'port' => 5538,
                        'username' => 'johndoe',
                        'password' => 'qwerty',
                        'vhost' => '/'
                    ]
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
                'magentoRelationShipsQueueConfig' => [
                    0 => [
                        'host' => 'localhost',
                        'port' => 5538,
                        'username' => 'johndoe',
                        'password' => 'qwerty',
                        'vhost' => '/'
                    ]
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
