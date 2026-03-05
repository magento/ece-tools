<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Test\Functional\Acceptance;

/**
 * Checks ActiveMQ configuration for PHP 8.5 and Magento 2.4.9-alpha-opensearch3.0
 *
 * @group php85
 */
class ActiveMq85Cest extends ActiveMqCest
{
    /**
     * @inheritdoc
     */
    protected function defaultConfigurationDataProvider(): array
    {
        return [
            'artemis-2.42' => [
                'version' => '2.4.9-alpha-opensearch3.0',
                'expectedHost' => 'activemq-artemis',
                'expectedPort' => 61616,
                'expectedUser' => 'admin',
                'expectedPassword' => 'admin',
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
            'custom-artemis-config' => [
                'version' => '2.4.9-alpha-opensearch3.0',
                'configuration' => [
                    'stage' => [
                        'deploy' => [
                            'QUEUE_CONFIGURATION' => [
                                '_merge' => false,
                                'default_connection'=> 'stomp',
                                'stomp' => [
                                    'host' => 'custom-activemq.test',
                                    'port' => 61617,
                                    'user' => 'activemq_user',
                                    'password' => 'activemq_password',
                                ],
                            ],
                        ],
                    ],
                ],
                'expectedQueueConfig' => [
                    'stomp' => [
                        'host' => 'custom-activemq.test',
                        'port' => 61617,
                        'user' => 'activemq_user',
                        'password' => 'activemq_password',
                    ],
                    'consumers_wait_for_messages' => 0,
                ],
            ],
            'merge-artemis-config' => [
                'version' => '2.4.9-alpha-opensearch3.0',
                'configuration' => [
                    'stage' => [
                        'deploy' => [
                            'QUEUE_CONFIGURATION' => [
                                '_merge' => true,
                                'default_connection'=> 'stomp',
                                'stomp' => [
                                    'user' => 'merged_user',
                                    'password' => 'merged_password',
                                ],
                            ],
                        ],
                    ],
                ],
                'expectedQueueConfig' => [
                    'stomp' => [
                        'host' => 'activemq-artemis',
                        'port' => 61616,
                        'user' => 'merged_user',
                        'password' => 'merged_password',
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
            'invalid-port' => [
                'version' => '2.4.9-alpha-opensearch3.0',
                'wrongConfiguration' => [
                    'stage' => [
                        'deploy' => [
                            'QUEUE_CONFIGURATION' => [
                                'default_connection'=> 'stomp',
                                'stomp' => [
                                    'host' => 'activemq-artemis',
                                    'port' => 'invalid_port',
                                    'user' => 'admin',
                                    'password' => 'admin',
                                ],
                            ],
                        ],
                    ],
                ],
                'buildSuccess' => true,
                'deploySuccess' => true,
                'errorDeployMessage' => '',
            ],
            'missing-host' => [
                'version' => '2.4.9-alpha-opensearch3.0',
                'wrongConfiguration' => [
                    'stage' => [
                        'deploy' => [
                            'QUEUE_CONFIGURATION' => [
                                '_merge' => false,
                                'default_connection'=> 'stomp',
                                'stomp' => [
                                    'port' => 61616,
                                    'user' => 'admin',
                                    'password' => 'admin',
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

    /**
     * @inheritdoc
     */
    protected function fallbackToRabbitMqDataProvider(): array
    {
        // Test with RabbitMQ version to verify AMQP configuration
        return [
            'rabbitmq-default-config-2.4.9-alpha-opensearch3.0' => [
                'version' => '2.4.9-alpha-opensearch3.0',
                'configuration' => [
                    'stage' => [
                        'deploy' => [
                            // No custom queue configuration, should use default RabbitMQ
                        ],
                    ],
                ],
                'expectedRabbitMqConfig' => [
                    'host' => 'rabbitmq',
                    'port' => 5672,
                    'user' => 'guest',
                    'password' => 'guest',
                ],
            ],
        ];
    }

    /**
     * @inheritdoc
     */
    protected function noMessageBrokerDataProvider(): array
    {
        // Test with no ActiveMQ and no RabbitMQ - validates database queue usage
        return [
            'db-queue-only-2.4.9-alpha-opensearch3.0' => [
                'version' => '2.4.9-alpha-opensearch3.0',
            ],
        ];
    }

    /**
     * Override testFallbackToRabbitMq to add RabbitMQ service instead of ActiveMQ
     *
     * @param        \CliTester           $I
     * @param        \Codeception\Example $data
     * @return       void
     * @throws       \Robo\Exception\TaskException
     * @dataProvider fallbackToRabbitMqDataProvider
     */
    public function testFallbackToRabbitMq(\CliTester $I, \Codeception\Example $data): void
    {
        $this->serviceToAdd = 'rabbitmq';
        parent::testFallbackToRabbitMq($I, $data);
    }

    /**
     * Override testNoMessageBroker to not add any message broker service
     *
     * @param        \CliTester           $I
     * @param        \Codeception\Example $data
     * @return       void
     * @throws       \Robo\Exception\TaskException
     * @dataProvider noMessageBrokerDataProvider
     */
    public function testNoMessageBroker(\CliTester $I, \Codeception\Example $data): void
    {
        $this->serviceToAdd = 'none';
        parent::testNoMessageBroker($I, $data);
    }
}
