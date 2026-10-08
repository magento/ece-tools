<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Test\Functional\Acceptance;

/**
 * Checks RabbitMQ configuration for PHP 8.5 and Magento 2.4.9 / 2.4.10
 *
 * @group php85
 */
class RabbitMq85Cest extends RabbitMqCest
{
    /**
     * Magento versions exercised by every data provider in this Cest.
     * Add a version here to fan it out across all providers below.
     */
    private const SUPPORTED_VERSIONS = ['2.4.9', '2.4.10-alpha'];

    /**
     * The version whose datasets shipped first. Its dataset keys are kept exactly as they were,
     * so existing datasets are never renamed; newer versions get a "-<version>" tag instead.
     */
    private const BASELINE_VERSION = '2.4.9';

    /**
     * RabbitMQ versions covered by the default configuration provider.
     */
    private const RABBITMQ_VERSIONS = ['4.2', '4.3'];

    /**
     * @inheritdoc
     */
    public function _before(\CliTester $I): void
    {
        $this->serviceToAdd = 'rabbitmq';
        $this->rabbitMqVersion = self::RABBITMQ_VERSIONS[0];
    }

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
            foreach (self::RABBITMQ_VERSIONS as $rabbitMqVersion) {
                $key = 'rabbitmq' . str_replace('.', '', $rabbitMqVersion)
                    . '-default-config-php85' . $this->versionTag($version);
                $data[$key] = [
                    'version' => $version,
                    'rabbitMqVersion' => $rabbitMqVersion,
                    'expectedHost' => 'rabbitmq',
                    'expectedPort' => 5672,
                    'expectedUser' => 'guest',
                    'expectedPassword' => 'guest',
                    'expectedConsumersWait' => 0,
                ];
            }
        }

        return $data;
    }

    protected function customConfigurationDataProvider(): array
    {
        $data = [];
        foreach (self::SUPPORTED_VERSIONS as $version) {
            $tag = $this->versionTag($version);
            $data['custom-rabbitmq-config-php85' . $tag] = $this->customRabbitMqConfigurationCase($version);
            $data['merge-rabbitmq-config-php85' . $tag] = $this->mergedRabbitMqConfigurationCase($version);
        }

        return $data;
    }

    private function customRabbitMqConfigurationCase(string $version): array
    {
        return [
            'version' => $version,
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
        ];
    }

    private function mergedRabbitMqConfigurationCase(string $version): array
    {
        return [
            'version' => $version,
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
        ];
    }

    protected function wrongConfigurationDataProvider(): array
    {
        $data = [];
        foreach (self::SUPPORTED_VERSIONS as $version) {
            $tag = $this->versionTag($version);
            $data['invalid-port-php85' . $tag] = $this->invalidPortCase($version);
            $data['missing-host-php85' . $tag] = $this->missingHostCase($version);
        }

        return $data;
    }

    private function invalidPortCase(string $version): array
    {
        return [
            'version' => $version,
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
        ];
    }

    private function missingHostCase(string $version): array
    {
        return [
            'version' => $version,
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
        ];
    }

    protected function noMessageBrokerDataProvider(): array
    {
        $data = [];
        foreach (self::SUPPORTED_VERSIONS as $version) {
            $data['db-queue-only-php85' . $this->versionTag($version)] = [
                'version' => $version,
            ];
        }

        return $data;
    }
}
