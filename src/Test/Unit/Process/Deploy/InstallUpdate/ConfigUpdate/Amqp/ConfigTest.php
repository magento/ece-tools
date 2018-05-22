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
     * @var ConfigMerger|Mock
     */
    private $configMergerMock;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        $this->environmentMock = $this->createMock(Environment::class);
        $this->stageConfigMock = $this->getMockForAbstractClass(DeployInterface::class);
        $this->configMergerMock = $this->createMock(ConfigMerger::class);

        $this->config = new Config(
            $this->environmentMock,
            $this->stageConfigMock,
            $this->configMergerMock
        );
    }

    /**
     * @param array $magentoRelationShipsQueueConfig
     * @param array $expectedQueueConfig
     * @dataProvider envQueueConfigNotExistDataProvider
     * @return void
     */
    public function testGetWhenEnvQueueConfigNotExist(
        $magentoRelationShipsQueueConfig,
        $expectedQueueConfig
    ) {
        $envQueueConfig = [];

        $this->stageConfigMock->expects($this->once())
            ->method('get')
            ->with(DeployInterface::VAR_QUEUE_CONFIGURATION)
            ->willReturn($envQueueConfig);
        $this->environmentMock->expects($this->exactly(3))
            ->method('getRelationship')
            ->withConsecutive(['rabbitmq'], ['mq'], ['amqp'])
            ->willReturnOnConsecutiveCalls([], [], $magentoRelationShipsQueueConfig);

        $this->configMergerMock->expects($this->once())
            ->method('isEmpty')
            ->with($envQueueConfig)
            ->willReturn(true);

        $this->configMergerMock->expects($this->never())
            ->method('isMergeRequired');
        $this->configMergerMock->expects($this->never())
            ->method('mergeConfigs');
        $this->configMergerMock->expects($this->never())
            ->method('clear');

        $this->assertEquals($expectedQueueConfig, $this->config->get());
    }

    /**
     * @return array
     */
    public function envQueueConfigNotExistDataProvider(): array
    {
        return [
            [
                'magentoRelationShipsQueueConfig' => [],
                'expectedQueueConfig' => [],
            ],
            [
                'magentoRelationShipsQueueConfig' => [
                    0 => [
                        'host' => 'localhost',
                        'port' => 5538,
                        'username' => 'johndoe',
                        'password' => 'qwerty',
                    ]
                ],
                'expectedQueueConfig' => [
                    'amqp' => [
                        'host' => 'localhost',
                        'port' => 5538,
                        'user' => 'johndoe',
                        'password' => 'qwerty',
                        'virtualhost' => '/'
                    ]
                ],
            ],
            [
                'magentoRelationShipsQueueConfig' => [
                    0 => [
                        'host' => 'localhost',
                        'port' => 5538,
                        'username' => 'johndoe',
                        'password' => 'qwerty',
                        'vhost' => 'some_virtual_host'
                    ]
                ],
                'expectedQueueConfig' => [
                    'amqp' => [
                        'host' => 'localhost',
                        'port' => 5538,
                        'user' => 'johndoe',
                        'password' => 'qwerty',
                        'virtualhost' => 'some_virtual_host'
                    ]
                ],
            ],
        ];
    }

    /**
     * @return void
     */
    public function testGetWithoutMergeConfigurations()
    {
        $envQueueConfig = ['some_queue_config'];

        $this->stageConfigMock->expects($this->once())
            ->method('get')
            ->with(DeployInterface::VAR_QUEUE_CONFIGURATION)
            ->willReturn($envQueueConfig);
        $this->environmentMock->expects($this->any())
            ->method('getRelationship');
        $this->configMergerMock->expects($this->once())
            ->method('isEmpty')
            ->with($envQueueConfig)
            ->willReturn(false);
        $this->configMergerMock->expects($this->once())
            ->method('isMergeRequired')
            ->with($envQueueConfig)
            ->willReturn(false);
        $this->configMergerMock->expects($this->never())
            ->method('mergeConfigs');
        $this->configMergerMock->expects($this->once())
            ->method('clear')
            ->with($envQueueConfig);

        $this->config->get();
    }

    /**
     * @return void
     */
    public function testGetWithMergeConfigurations()
    {
        $envQueueConfig = ['some_queue_config'];
        $magentoRelationShipsQueueConfig = [
            0 => [
                'host' => 'localhost',
                'port' => 5538,
                'username' => 'johndoe',
                'password' => 'qwerty',
            ]
        ];
        $mqConfig = [
            'amqp' => [
                'host' => 'localhost',
                'port' => 5538,
                'user' => 'johndoe',
                'password' => 'qwerty',
                'virtualhost' => '/'
            ]
        ];

        $this->stageConfigMock->expects($this->once())
            ->method('get')
            ->with(DeployInterface::VAR_QUEUE_CONFIGURATION)
            ->willReturn($envQueueConfig);
        $this->environmentMock->expects($this->once())
            ->method('getRelationship')
            ->with('rabbitmq')
            ->willReturn($magentoRelationShipsQueueConfig);
        $this->configMergerMock->expects($this->once())
            ->method('isEmpty')
            ->with($envQueueConfig)
            ->willReturn(false);
        $this->configMergerMock->expects($this->once())
            ->method('isMergeRequired')
            ->with($envQueueConfig)
            ->willReturn(true);
        $this->configMergerMock->expects($this->once())
            ->method('mergeConfigs')
            ->with($mqConfig, $envQueueConfig)
            ->willReturn([]);
        $this->configMergerMock->expects($this->never())
            ->method('clear')
            ->with($envQueueConfig);

        $this->config->get();
    }
}
