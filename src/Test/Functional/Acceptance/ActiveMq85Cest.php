<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Test\Functional\Acceptance;

/**
 * Checks ActiveMQ configuration for PHP 8.5 and Magento 2.4.9 / 2.4.10
 *
 * @group php85
 */
class ActiveMq85Cest extends ActiveMqCest
{
    /**
     * Magento versions exercised by every data provider in this Cest.
     * Add a version here to fan it out across all providers below.
     */
    private const SUPPORTED_VERSIONS = ['2.4.9', '2.4.10-alpha'];

    /**
     * The version whose datasets shipped first. Its dataset keys are kept exactly as they were
     * (the default/custom/wrong providers key on the Artemis version only for this version), so
     * existing datasets are never renamed; newer versions get a "-<version>" tag instead.
     */
    private const BASELINE_VERSION = '2.4.9';

    /**
     * ActiveMQ Artemis versions covered by the default/custom/wrong/fallback providers.
     */
    private const ARTEMIS_VERSIONS = ['2.51.0', '2.42.0'];

    /** @var string */
    protected string $magentoCloudTemplate = self::BASELINE_VERSION;

    /**
     * Dataset-key tag for a Magento version: empty for the baseline (keeps its original keys),
     * "-<version>" for any newer version.
     *
     * @param  string $version
     * @return string
     */
    private function versionTag(string $version): string
    {
        return $version === self::BASELINE_VERSION ? '' : '-' . $version;
    }

    protected function defaultConfigurationDataProvider(): array
    {
        $data = [];
        foreach (self::SUPPORTED_VERSIONS as $version) {
            foreach (self::ARTEMIS_VERSIONS as $artemisVersion) {
                $data['artemis-' . $artemisVersion . $this->versionTag($version)]
                    = $this->defaultConfigurationCase($version, $artemisVersion);
            }
        }

        return $data;
    }

    /**
     * @param  string $version
     * @param  string $artemisVersion
     * @return array
     */
    private function defaultConfigurationCase(string $version, string $artemisVersion): array
    {
        return [
            'version' => $version,
            'expectedHost' => 'activemq-artemis',
            'activemqArtemisVersion' => $artemisVersion,
            'expectedPort' => 61616,
            'expectedUser' => 'admin',
            'expectedPassword' => 'admin',
            'expectedVirtualHost' => '/',
            'expectedConsumersWait' => 0,
        ];
    }

    /**
     * @inheritdoc
     */
    protected function customConfigurationDataProvider(): array
    {
        $data = [];
        foreach (self::SUPPORTED_VERSIONS as $version) {
            $tag = $this->versionTag($version);
            foreach (self::ARTEMIS_VERSIONS as $artemisVersion) {
                $data['custom-artemis-config-' . $artemisVersion . $tag]
                    = $this->customArtemisConfigurationCase($version, $artemisVersion);
            }
            foreach (self::ARTEMIS_VERSIONS as $artemisVersion) {
                $data['merge-artemis-config-' . $artemisVersion . $tag]
                    = $this->mergedArtemisConfigurationCase($version, $artemisVersion);
            }
        }

        return $data;
    }

    private function customArtemisConfigurationCase(string $version, string $activemqArtemisVersion): array
    {
        return [
            'version' => $version,
            'activemqArtemisVersion' => $activemqArtemisVersion,
            'configuration' => [
                'stage' => [
                    'deploy' => [
                        'QUEUE_CONFIGURATION' => [
                            '_merge' => false,
                            'default_connection' => 'stomp',
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

    private function mergedArtemisConfigurationCase(string $version, string $activemqArtemisVersion): array
    {
        return [
            'version' => $version,
            'activemqArtemisVersion' => $activemqArtemisVersion,
            'configuration' => [
                'stage' => [
                    'deploy' => [
                        'QUEUE_CONFIGURATION' => [
                            '_merge' => true,
                            'default_connection' => 'stomp',
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
        $data = [];
        foreach (self::SUPPORTED_VERSIONS as $version) {
            $tag = $this->versionTag($version);
            foreach (self::ARTEMIS_VERSIONS as $artemisVersion) {
                $data['invalid-port-' . $artemisVersion . $tag]
                    = $this->invalidPortCase($version, $artemisVersion);
            }
            foreach (self::ARTEMIS_VERSIONS as $artemisVersion) {
                $data['missing-host-' . $artemisVersion . $tag]
                    = $this->missingHostCase($version, $artemisVersion);
            }
        }

        return $data;
    }

    private function invalidPortCase(string $version, string $activemqArtemisVersion): array
    {
        return [
            'version' => $version,
            'activemqArtemisVersion' => $activemqArtemisVersion,
            'wrongConfiguration' => [
                'stage' => [
                    'deploy' => [
                        'QUEUE_CONFIGURATION' => [
                            'default_connection' => 'stomp',
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
        ];
    }

    private function missingHostCase(string $version, string $activemqArtemisVersion): array
    {
        return [
            'version' => $version,
            'activemqArtemisVersion' => $activemqArtemisVersion,
            'wrongConfiguration' => [
                'stage' => [
                    'deploy' => [
                        'QUEUE_CONFIGURATION' => [
                            '_merge' => false,
                            'default_connection' => 'stomp',
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
        ];
    }

    /**
     * @inheritdoc
     */
    protected function fallbackToRabbitMqDataProvider(): array
    {
        // Test with RabbitMQ version to verify AMQP configuration
        $data = [];
        foreach (self::SUPPORTED_VERSIONS as $version) {
            foreach (self::ARTEMIS_VERSIONS as $artemisVersion) {
                $data['rabbitmq-default-config-' . $version . '-' . $artemisVersion]
                    = $this->fallbackToRabbitMqCase($version, $artemisVersion);
            }
        }

        return $data;
    }

    private function fallbackToRabbitMqCase(string $version, string $activemqArtemisVersion): array
    {
        return [
            'version' => $version,
            'activemqArtemisVersion' => $activemqArtemisVersion,
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
        ];
    }

    /**
     * @inheritdoc
     */
    protected function noMessageBrokerDataProvider(): array
    {
        // Test with no ActiveMQ and no RabbitMQ - validates database queue usage
        $data = [];
        foreach (self::SUPPORTED_VERSIONS as $version) {
            $data['db-queue-only-' . $version] = [
                'version' => $version,
            ];
        }

        return $data;
    }

    /**
     * @inheritdoc
     */
    protected function bothBrokersDataProvider(): array
    {
        $data = [];
        foreach (self::SUPPORTED_VERSIONS as $version) {
            $data['both-brokers-' . $version . '-2.51.0'] = $this->bothBrokersCase($version, '2.51.0');
        }

        return $data;
    }

    private function bothBrokersCase(string $version, string $activemqArtemisVersion): array
    {
        return [
            'version' => $version,
            'activemqArtemisVersion' => $activemqArtemisVersion,
            'expectedStompConfig' => [
                'host' => 'activemq-artemis',
                'port' => 61616,
                'user' => 'admin',
                'password' => 'admin',
            ],
            'expectedAmqpConfig' => [
                'host' => 'rabbitmq',
                'port' => 5672,
                'user' => 'guest',
                'password' => 'guest',
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
