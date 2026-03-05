<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Test\Unit\Config;

use Magento\MagentoCloud\Config\ConfigException;
use Magento\MagentoCloud\Config\ConfigMerger;
use Magento\MagentoCloud\Package\MagentoVersion;
use Magento\MagentoCloud\Config\Stage\DeployInterface;
use Magento\MagentoCloud\Package\UndefinedPackageException;
use Magento\MagentoCloud\Config\Amqp;
use Magento\MagentoCloud\Service\ActiveMq;
use Magento\MagentoCloud\Service\RabbitMq;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\Exception;
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
    protected Amqp $config;

    /**
     * @var ActiveMq|MockObject
     */
    protected $activeMq;

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
     * @throws     Exception
     */
    protected function setUp(): void
    {
        $this->activeMq = $this->createMock(ActiveMq::class);
        $this->rabbitMq = $this->createMock(RabbitMq::class);
        $this->stageConfigMock = $this->createMock(DeployInterface::class);
        $this->magentoVersionMock = $this->createMock(MagentoVersion::class);

        $this->config = new Amqp(
            $this->activeMq,
            $this->rabbitMq,
            $this->stageConfigMock,
            new ConfigMerger(),
            $this->magentoVersionMock
        );
    }

    /**
     * Test getConfig method.
     *
     * @param  array $customQueueConfig
     * @param  array $amqpServiceConfig
     * @param  bool  $isGreaterOrEqualReturns
     * @param  bool  $consumersWaitMaxMessages
     * @param  int   $countCallGetConfig
     * @param  array $expectedQueueConfig
     * @return void
     * @throws UndefinedPackageException|ConfigException
     *
     * @dataProvider getConfigDataProvider
     */
    #[DataProvider('getConfigDataProvider')]
    public function testGetConfig(
        array $customQueueConfig,
        array $amqpServiceConfig,
        bool $isGreaterOrEqualReturns,
        bool $consumersWaitMaxMessages,
        int $countCallGetConfig,
        array $expectedQueueConfig
    ): void {
        $series = [
            [[DeployInterface::VAR_QUEUE_CONFIGURATION], $customQueueConfig],
            [[DeployInterface::VAR_CONSUMERS_WAIT_FOR_MAX_MESSAGES], $consumersWaitMaxMessages],
        ];
        $this->stageConfigMock->expects($this->exactly($countCallGetConfig))
            ->method('get')
            ->willReturnCallback(
                function (...$args) use (&$series) {
                    [$expectedArgs, $return] = array_shift($series);
                    $this->assertSame($expectedArgs, $args);

                    return $return;
                }
            );
        $this->activeMq->expects($this->once())
            ->method('getConfiguration')
            ->willReturn([]);
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
     * Data provider for testGetConfig.
     *
     * @return array
     *
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public static function getConfigDataProvider(): array
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

    /**
     * Test that ActiveMQ takes priority over RabbitMQ
     */
    public function testActiveMqPriorityOverRabbitMq(): void
    {
        $activeMqConfig = [
            'host' => 'activemq-host',
            'port' => 61616,
            'username' => 'activemq_user',
            'password' => 'activemq_password',
            'vhost' => '/activemq'
        ];

        $rabbitMqConfig = [
            'host' => 'rabbitmq-host',
            'port' => 5672,
            'username' => 'rabbitmq_user',
            'password' => 'rabbitmq_password',
            'vhost' => '/rabbitmq'
        ];

        $this->stageConfigMock->expects($this->exactly(2))
            ->method('get')
            ->willReturnCallback(
                function ($key) {
                    if ($key === DeployInterface::VAR_QUEUE_CONFIGURATION) {
                        return [];
                    }
                    if ($key === DeployInterface::VAR_CONSUMERS_WAIT_FOR_MAX_MESSAGES) {
                        return false;
                    }
                    return null;
                }
            );

        // ActiveMQ is available and should be used
        $this->activeMq->expects($this->once())
            ->method('getConfiguration')
            ->willReturn($activeMqConfig);

        // RabbitMQ should not be called since ActiveMQ is available
        $this->rabbitMq->expects($this->never())
            ->method('getConfiguration');

        $this->magentoVersionMock->expects($this->once())
            ->method('isGreaterOrEqual')
            ->with('2.2')
            ->willReturn(true);

        $expectedConfig = [
            'amqp' => [
                'host' => 'activemq-host',
                'port' => 61616,
                'user' => 'activemq_user',
                'password' => 'activemq_password',
                'virtualhost' => '/activemq',
            ],
            'consumers_wait_for_messages' => 0
        ];

        try {
            $this->assertEquals($expectedConfig, $this->config->getConfig());
        } catch (ConfigException|UndefinedPackageException $e) {
        }
    }

    /**
     * Test that RabbitMQ is used as fallback when ActiveMQ is not available
     *
     * @return void
     */
    public function testRabbitMqFallbackWhenActiveMqNotAvailable(): void
    {
        $rabbitMqConfig = [
            'host' => 'rabbitmq-host',
            'port' => 5672,
            'username' => 'rabbitmq_user',
            'password' => 'rabbitmq_password',
            'vhost' => '/rabbitmq'
        ];

        $this->stageConfigMock->expects($this->exactly(2))
            ->method('get')
            ->willReturnCallback(
                function ($key) {
                    if ($key === DeployInterface::VAR_QUEUE_CONFIGURATION) {
                        return [];
                    }
                    if ($key === DeployInterface::VAR_CONSUMERS_WAIT_FOR_MAX_MESSAGES) {
                        return false;
                    }
                    return null;
                }
            );

        // ActiveMQ is not available
        $this->activeMq->expects($this->once())
            ->method('getConfiguration')
            ->willReturn([]);

        // RabbitMQ should be used as fallback
        $this->rabbitMq->expects($this->once())
            ->method('getConfiguration')
            ->willReturn($rabbitMqConfig);

        $this->magentoVersionMock->expects($this->once())
            ->method('isGreaterOrEqual')
            ->with('2.2')
            ->willReturn(true);

        $expectedConfig = [
            'amqp' => [
                'host' => 'rabbitmq-host',
                'port' => 5672,
                'user' => 'rabbitmq_user',
                'password' => 'rabbitmq_password',
                'virtualhost' => '/rabbitmq',
            ],
            'consumers_wait_for_messages' => 0
        ];

        try {
            $this->assertEquals($expectedConfig, $this->config->getConfig());
        } catch (ConfigException|UndefinedPackageException $e) {
        }
    }
}
