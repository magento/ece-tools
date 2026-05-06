<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Test\Functional\Acceptance;

/**
 * Checks ActiveMQ configuration for PHP 8.1 and Magento 2.4.5
 *
 * @group php81
 */
class ActiveMq81Cest extends ActiveMqCest
{
    /**
     * @inheritdoc
     */
    protected function defaultConfigurationDataProvider(): array
    {
        return [
            'artemis-2.51.0-php81' => [
                'version' => '2.4.5',
                'expectedHost' => 'activemq-artemis',
                'activemqArtemisVersion' => '2.51.0',
                'expectedPort' => 61616,
                'expectedUser' => 'admin',
                'expectedPassword' => 'admin',
                'expectedVirtualHost' => '/',
                'expectedConsumersWait' => 0,
            ],
            'artemis-2.42.0-php81' => [
                'version' => '2.4.5',
                'expectedHost' => 'activemq-artemis',
                'activemqArtemisVersion' => '2.42.0',
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
            'custom-artemis-config-2.51.0-php81' => $this->customArtemisConfigurationCase('2.51.0'),
            'custom-artemis-config-2.42.0-php81' => $this->customArtemisConfigurationCase('2.42.0'),
            'merge-artemis-config-2.51.0-php81' => $this->mergedArtemisConfigurationCase('2.51.0'),
            'merge-artemis-config-2.42.0-php81' => $this->mergedArtemisConfigurationCase('2.42.0'),
        ];
    }

    private function customArtemisConfigurationCase(string $activemqArtemisVersion): array
    {
        return [
            'version' => '2.4.5',
            'activemqArtemisVersion' => $activemqArtemisVersion,
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
        ];
    }

    private function mergedArtemisConfigurationCase(string $activemqArtemisVersion): array
    {
        return [
            'version' => '2.4.5',
            'activemqArtemisVersion' => $activemqArtemisVersion,
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
        ];
    }

    /**
     * @inheritdoc
     */
    protected function wrongConfigurationDataProvider(): array
    {
        return [
            'invalid-port-2.51.0-php81' => [
                'version' => '2.4.5',
                'activemqArtemisVersion' => '2.51.0',
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
            'invalid-port-2.42.0-php81' => [
                'version' => '2.4.5',
                'activemqArtemisVersion' => '2.42.0',
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
            'missing-host-2.51.0-php81' => [
                'version' => '2.4.5',
                'activemqArtemisVersion' => '2.51.0',
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
            'missing-host-2.42.0-php81' => [
                'version' => '2.4.5',
                'activemqArtemisVersion' => '2.42.0',
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
        return [
            'rabbitmq-default-config-2.4.5-2.51.0-php81' => [
                'version' => '2.4.5',
                'activemqArtemisVersion' => '2.51.0',
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
            'rabbitmq-default-config-2.4.5-2.42.0-php81' => [
                'version' => '2.4.5',
                'activemqArtemisVersion' => '2.42.0',
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
        return [
            'db-queue-only-php81' => [
                'version' => '2.4.5',
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
