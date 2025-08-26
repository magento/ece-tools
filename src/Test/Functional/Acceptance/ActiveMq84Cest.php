<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 *
 * @category Magento
 * @package  Magento\MagentoCloud\Test\Functional\Acceptance
 * @author   Magento Core Team <core@magentocommerce.com>
 * @license  https://opensource.org/licenses/OSL-3.0 Open Software License (OSL 3.0)
 * @link     https://magento.com
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Test\Functional\Acceptance;

/**
 * ActiveMQ acceptance tests for PHP 8.4 and Magento 2.4.x
 *
 * @group php84
 *
 * @category Magento
 * @package  Magento\MagentoCloud\Test\Functional\Acceptance
 * @author   Magento Core Team <core@magentocommerce.com>
 * @license  https://opensource.org/licenses/OSL-3.0 Open Software License (OSL 3.0)
 * @link     https://magento.com
 */
class ActiveMq84Cest extends ActiveMqCest
{
    /**
     * @inheritdoc
     */
    protected function defaultConfigurationDataProvider(): array
    {
        return [
            'artemis-2.42-magento-2.4.8' => [
                'version' => '2.4.8',
                'expectedHost' => 'activemq-artemis',
                'expectedPort' => 61616,
                'expectedUser' => 'guest',
                'expectedPassword' => 'guest',
                'expectedVirtualHost' => '/',
                'expectedConsumersWait' => 0,
            ],
        ];
    }

    /**
     * @inheritdoc
     */
    protected function customConfigurationDataProvider(): array
    {
        return [
            'custom-artemis-config-2.4.8' => [
                'version' => '2.4.8',
                'configuration' => [
                    'stage' => [
                        'deploy' => [
                            'QUEUE_CONFIGURATION' => [
                                '_merge' => false,
                                'amqp' => [
                                    'host' => 'custom-activemq.test',
                                    'port' => 61617,
                                    'user' => 'activemq_user',
                                    'password' => 'activemq_password',
                                    'virtualhost' => '/custom',
                                ],
                            ],
                        ],
                    ],
                ],
                'expectedQueueConfig' => [
                    'amqp' => [
                        'host' => 'custom-activemq.test',
                        'port' => 61617,
                        'user' => 'activemq_user',
                        'password' => 'activemq_password',
                        'virtualhost' => '/custom',
                    ],
                    'consumers_wait_for_messages' => 0,
                ],
            ],
            'merge-artemis-config-2.4.8' => [
                'version' => '2.4.8',
                'configuration' => [
                    'stage' => [
                        'deploy' => [
                            'QUEUE_CONFIGURATION' => [
                                '_merge' => true,
                                'amqp' => [
                                    'user' => 'merged_user',
                                    'password' => 'merged_password',
                                ],
                            ],
                        ],
                    ],
                ],
                'expectedQueueConfig' => [
                    'amqp' => [
                        'host' => 'activemq-artemis',
                        'port' => 61616,
                        'user' => 'merged_user',
                        'password' => 'merged_password',
                        'virtualhost' => '/',
                    ],
                    'consumers_wait_for_messages' => 0,
                ],
            ],
        ];
    }

    /**
     * @inheritdoc
     */
    protected function wrongConfigurationDataProvider(): array
    {
        return [
            'invalid-port-2.4.8' => [
                'version' => '2.4.8',
                'wrongConfiguration' => [
                    'stage' => [
                        'deploy' => [
                            'QUEUE_CONFIGURATION' => [
                                'amqp' => [
                                    'host' => 'activemq-artemis',
                                    'port' => 'invalid_port',
                                    'user' => 'guest',
                                    'password' => 'guest',
                                ],
                            ],
                        ],
                    ],
                ],
                'buildSuccess' => true,
                'deploySuccess' => false,
                'errorDeployMessage' => 'Invalid port configuration',
            ],
            'missing-host-2.4.8' => [
                'version' => '2.4.8',
                'wrongConfiguration' => [
                    'stage' => [
                        'deploy' => [
                            'QUEUE_CONFIGURATION' => [
                                'amqp' => [
                                    'port' => 61616,
                                    'user' => 'guest',
                                    'password' => 'guest',
                                ],
                            ],
                        ],
                    ],
                ],
                'buildSuccess' => true,
                'deploySuccess' => false,
                'errorDeployMessage' => 'Host is required',
            ],
        ];
    }

    /**
     * @inheritdoc
     */
    protected function connectionFailureDataProvider(): array
    {
        return [
            'unreachable-host-2.4.8' => [
                'version' => '2.4.8',
                'configuration' => [
                    'stage' => [
                        'deploy' => [
                            'QUEUE_CONFIGURATION' => [
                                'amqp' => [
                                    'host' => 'unreachable-activemq.test',
                                    'port' => 61616,
                                    'user' => 'guest',
                                    'password' => 'guest',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            'wrong-port-2.4.8' => [
                'version' => '2.4.8',
                'configuration' => [
                    'stage' => [
                        'deploy' => [
                            'QUEUE_CONFIGURATION' => [
                                'amqp' => [
                                    'host' => 'activemq-artemis',
                                    'port' => 99999,
                                    'user' => 'guest',
                                    'password' => 'guest',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * @inheritdoc
     */
    protected function fallbackToRabbitMqDataProvider(): array
    {
        return [
            'artemis-unavailable-rabbitmq-available-2.4.8' => [
                'version' => '2.4.8',
                'configuration' => [
                    'stage' => [
                        'deploy' => [
                            // No ActiveMQ service configured, should fallback to RabbitMQ
                        ],
                    ],
                ],
                'expectedRabbitMqConfig' => [
                    'host' => 'rabbitmq',
                    'port' => 5672,
                    'user' => 'guest',
                    'password' => 'guest',
                    'virtualhost' => '/',
                ],
            ],
        ];
    }

    /**
     * @inheritdoc
     */
    protected function noMessageBrokerDataProvider(): array
    {
        return [
            'no-artemis-no-rabbitmq-2.4.8' => [
                'version' => '2.4.8',
            ],
        ];
    }
}
