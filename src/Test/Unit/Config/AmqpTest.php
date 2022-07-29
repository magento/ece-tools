<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Test\Unit\Config;

use Magento\MagentoCloud\Config\ConfigMerger;
use Magento\MagentoCloud\Package\MagentoVersion;
use Magento\MagentoCloud\Config\Stage\DeployInterface;
use Magento\MagentoCloud\Package\UndefinedPackageException;
use Magento\MagentoCloud\Config\Amqp;
use Magento\MagentoCloud\Service\RabbitMq;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @inheritdoc
 */
class AmqpTest extends TestCase
{
    /**
     * @var Amqp
     */
    protected $config;

    /**
     * @var RabbitMq|MockObject
     */
    protected $rabbitMq;

    /**
     * @var DeployInterface|MockObject
     */
    private $stageConfigMock;

    /**
     * @var MagentoVersion|MockObject
     */
    private $magentoVersionMock;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->rabbitMq = $this->createMock(RabbitMq::class);
        $this->stageConfigMock = $this->getMockForAbstractClass(DeployInterface::class);
        $this->magentoVersionMock = $this->createMock(MagentoVersion::class);

        $this->config = new Amqp(
            $this->rabbitMq,
            $this->stageConfigMock,
            new ConfigMerger(),
            $this->magentoVersionMock
        );
    }

    /**
     * @param array $customQueueConfig
     * @param array $amqpServiceConfig
     * @param bool $isGreaterOrEqualReturns
     * @param bool $consumersWaitMaxMessages
     * @param int $countCallGetConfig
     * @param array $expectedQueueConfig
     * @throws UndefinedPackageException
     *
     * @dataProvider getConfigDataProvider
     */
    public function testGetConfig(
        array $customQueueConfig,
        array $amqpServiceConfig,
        bool $isGreaterOrEqualReturns,
        bool $consumersWaitMaxMessages,
        int $countCallGetConfig,
        array $expectedQueueConfig
    ): void {
        $this->stageConfigMock->expects($this->exactly($countCallGetConfig))
            ->method('get')
            ->withConsecutive(
                [DeployInterface::VAR_QUEUE_CONFIGURATION],
                [DeployInterface::VAR_CONSUMERS_WAIT_FOR_MAX_MESSAGES]
            )
            ->willReturnOnConsecutiveCalls($customQueueConfig, $consumersWaitMaxMessages);
        $this->rabbitMq->expects($this->once())
            ->method('getConfiguration')
            ->willReturn($amqpServiceConfig);
        $this->magentoVersionMock->expects($this->once())
            ->method('isGreaterOrEqual')
            ->with('2.2')
            ->willReturn($isGreaterOrEqualReturns);

        $this->assertEquals($expectedQueueConfig, $this->config->getConfig());
    }

    /**
     * @return array
     *
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function getConfigDataProvider(): array
    {
        return [
            'queue configuration does not exist' => [
                'customQueueConfig' => [],
                'amqpServiceConfig' => [],
                'isGreaterOrEqualReturns' => false,
                'consumersWaitMaxMessages' => false,
                'countCallGetConfig' => 1,
                'expectedQueueConfig' => [],
            ],
            'queue configuration does not exist and Magento >= 2.2.0' => [
                'customQueueConfig' => [],
                'amqpServiceConfig' => [],
                'isGreaterOrEqualReturns' => true,
                'consumersWaitMaxMessages' => true,
                'countCallGetConfig' => 2,
                'expectedQueueConfig' => ['consumers_wait_for_messages' => 1],
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
                'isGreaterOrEqualReturns' => false,
                'consumersWaitMaxMessages' => false,
                'countCallGetConfig' => 1,
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
            'only custom queue configuration exists and Magento >= 2.2.0' => [
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
                'isGreaterOrEqualReturns' => true,
                'consumersWaitMaxMessages' => false,
                'countCallGetConfig' => 2,
                'expectedQueueConfig' => [
                    'amqp' => [
                        'host' => 'custom_host',
                        'port' => 3333,
                        'user' => 'custom_user',
                        'password' => 'custom_password',
                        'virtualhost' => 'custom_vhost',
                    ],
                    'consumers_wait_for_messages' => 0
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
                'isGreaterOrEqualReturns' => false,
                'consumersWaitMaxMessages' => false,
                'countCallGetConfig' => 1,
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
            'custom and relationship queue configurations exists without merge and Magento >= 2.2.0' => [
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
                'isGreaterOrEqualReturns' => true,
                'consumersWaitMaxMessages' => true,
                'countCallGetConfig' => 2,
                'expectedQueueConfig' => [
                    'amqp' => [
                        'host' => 'custom_host',
                        'port' => 3333,
                        'user' => 'custom_user',
                        'password' => 'custom_password',
                        'virtualhost' => 'custom_vhost',
                    ],
                    'consumers_wait_for_messages' => 1
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
                'isGreaterOrEqualReturns' => false,
                'consumersWaitMaxMessages' => false,
                'countCallGetConfig' => 1,
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
            'custom and relationship queue configurations exists with merge and Magento >= 2.2.0' => [
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
                'isGreaterOrEqualReturns' => true,
                'consumersWaitMaxMessages' => false,
                'countCallGetConfig' => 2,
                'expectedQueueConfig' => [
                    'amqp' => [
                        'host' => 'localhost',
                        'port' => 5538,
                        'user' => 'custom_user',
                        'password' => 'custom_password',
                        'virtualhost' => 'custom_vhost',
                    ],
                    'consumers_wait_for_messages' => 0
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
                'isGreaterOrEqualReturns' => false,
                'consumersWaitMaxMessages' => false,
                'countCallGetConfig' => 1,
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
            'only relationships queue configuration exists and Magento >= 2.2.0' => [
                'customQueueConfig' => [],
                'amqpServiceConfig' => [
                    'host' => 'localhost',
                    'port' => 5538,
                    'username' => 'johndoe',
                    'password' => 'qwerty',
                    'vhost' => '/'
                ],
                'isGreaterOrEqualReturns' => true,
                'consumersWaitMaxMessages' => true,
                'countCallGetConfig' => 2,
                'expectedQueueConfig' => [
                    'amqp' => [
                        'host' => 'localhost',
                        'port' => 5538,
                        'user' => 'johndoe',
                        'password' => 'qwerty',
                        'virtualhost' => '/',
                    ],
                    'consumers_wait_for_messages' => 1
                ],
            ],
        ];
    }
}
