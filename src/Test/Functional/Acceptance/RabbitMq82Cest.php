<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Test\Functional\Acceptance;

/**
 * Checks RabbitMQ configuration for PHP 8.2 and Magento 2.4.6
 *
 * @group php82
 */
class RabbitMq82Cest extends RabbitMqCest
{
    /**
     * @var string
     */
    protected string $magentoCloudTemplate = '2.4.6';

    protected function defaultConfigurationDataProvider(): array
    {
        return [
            'rabbitmq42-default-config-php82' => [
                'version' => '2.4.6',
                'rabbitMqVersion' => '4.2',
                'expectedHost' => 'rabbitmq',
                'expectedPort' => 5672,
                'expectedUser' => 'guest',
                'expectedPassword' => 'guest',
                'expectedConsumersWait' => 0,
            ],
            'rabbitmq43-default-config-php82' => [
                'version' => '2.4.6',
                'rabbitMqVersion' => '4.3',
                'expectedHost' => 'rabbitmq',
                'expectedPort' => 5672,
                'expectedUser' => 'guest',
                'expectedPassword' => 'guest',
                'expectedConsumersWait' => 0,
            ],
        ];
    }

    protected function customConfigurationDataProvider(): array
    {
        return [
            'custom-rabbitmq-config-php82' => [
                'version' => '2.4.6',
                'configuration' => [
                    'stage' => [
                        'deploy' => [
                            'QUEUE_CONFIGURATION' => [
                                '_merge' => false,
                                'default_connection' => 'amqp',
                                'amqp' => [
                                    'host' => 'rabbitmq',
                                    'port' => 5672,
                                    'user' => 'rabbitmq_user',
                                    'password' => 'rabbitmq_password',
                                ],
                            ],
                        ],
                    ],
                ],
                'expectedQueueConfig' => [
                    'amqp' => [
                        'host' => 'rabbitmq',
                        'port' => 5672,
                        'user' => 'rabbitmq_user',
                        'password' => 'rabbitmq_password',
                    ],
                    'consumers_wait_for_messages' => 0,
                ],
            ],
            'merge-rabbitmq-config-php82' => [
                'version' => '2.4.6',
                'configuration' => [
                    'stage' => [
                        'deploy' => [
                            'QUEUE_CONFIGURATION' => [
                                '_merge' => true,
                                'default_connection' => 'amqp',
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
                        'host' => 'rabbitmq',
                        'port' => 5672,
                        'user' => 'merged_user',
                        'password' => 'merged_password',
                    ],
                    'consumers_wait_for_messages' => 0,
                ],
            ],
        ];
    }

    protected function wrongConfigurationDataProvider(): array
    {
        return [
            'invalid-port-php82' => [
                'version' => '2.4.6',
                'wrongConfiguration' => [
                    'stage' => [
                        'deploy' => [
                            'QUEUE_CONFIGURATION' => [
                                'default_connection' => 'amqp',
                                'amqp' => [
                                    'host' => 'rabbitmq',
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
                'errorDeployMessage' => 'Parameter validation failed',
            ],
            'missing-host-php82' => [
                'version' => '2.4.6',
                'wrongConfiguration' => [
                    'stage' => [
                        'deploy' => [
                            'QUEUE_CONFIGURATION' => [
                                '_merge' => false,
                                'default_connection' => 'amqp',
                                'amqp' => [
                                    'port' => 5672,
                                    'user' => 'guest',
                                    'password' => 'guest',
                                ],
                            ],
                        ],
                    ],
                ],
                'buildSuccess' => true,
                'deploySuccess' => true,
                'errorDeployMessage' => '',
            ],
        ];
    }

    protected function noMessageBrokerDataProvider(): array
    {
        return [
            'db-queue-only-php82' => [
                'version' => '2.4.6',
            ],
        ];
    }
}
