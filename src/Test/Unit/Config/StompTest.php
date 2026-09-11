<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Test\Unit\Config;

use Magento\MagentoCloud\Config\ConfigException;
use Magento\MagentoCloud\Config\ConfigMerger;
use Magento\MagentoCloud\Config\Stage\DeployInterface;
use Magento\MagentoCloud\Config\Stomp;
use Magento\MagentoCloud\Package\MagentoVersion;
use Magento\MagentoCloud\Package\UndefinedPackageException;
use Magento\MagentoCloud\Service\ActiveMq;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @inheritdoc
 */
class StompTest extends TestCase
{
    /**
     * @var Stomp
     */
    private Stomp $config;

    /**
     * @var ActiveMq|MockObject
     */
    private $activeMq;

    /**
     * @var DeployInterface|MockObject
     */
    private $stageConfigMock;

    /**
     * @var MagentoVersion|MockObject
     */
    private $magentoVersionMock;

    /**
     * @throws Exception
     */
    protected function setUp(): void
    {
        $this->activeMq = $this->createMock(ActiveMq::class);
        $this->stageConfigMock = $this->createMock(DeployInterface::class);
        $this->magentoVersionMock = $this->createMock(MagentoVersion::class);

        $this->config = new Stomp(
            $this->activeMq,
            $this->stageConfigMock,
            new ConfigMerger(),
            $this->magentoVersionMock
        );
    }

    /**
     * @param array $customQueueConfig
     * @param array $activeMqServiceConfig
     * @param bool  $isGreaterOrEqualReturns
     * @param bool  $consumersWaitMaxMessages
     * @param int   $countCallGetConfig
     * @param array $expectedQueueConfig
     * @return void
     * @throws UndefinedPackageException|ConfigException
     *
     * @dataProvider getConfigDataProvider
     */
    #[DataProvider('getConfigDataProvider')]
    public function testGetConfig(
        array $customQueueConfig,
        array $activeMqServiceConfig,
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
            ->willReturn($activeMqServiceConfig);
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
    public static function getConfigDataProvider(): array
    {
        return [
            'no queue configuration and no ActiveMQ relationship' => [
                'customQueueConfig' => [],
                'activeMqServiceConfig' => [],
                'isGreaterOrEqualReturns' => false,
                'consumersWaitMaxMessages' => false,
                'countCallGetConfig' => 1,
                'expectedQueueConfig' => [],
            ],
            'no queue configuration and no ActiveMQ relationship, Magento >= 2.2.0' => [
                'customQueueConfig' => [],
                'activeMqServiceConfig' => [],
                'isGreaterOrEqualReturns' => true,
                'consumersWaitMaxMessages' => true,
                'countCallGetConfig' => 2,
                'expectedQueueConfig' => ['consumers_wait_for_messages' => 1],
            ],
            'only custom queue configuration exists (no merge)' => [
                'customQueueConfig' => [
                    'stomp' => [
                        'host' => 'custom_host',
                        'port' => '61617',
                        'user' => 'custom_user',
                        'password' => 'custom_password',
                    ],
                    'default_connection' => 'stomp',
                ],
                'activeMqServiceConfig' => [],
                'isGreaterOrEqualReturns' => false,
                'consumersWaitMaxMessages' => false,
                'countCallGetConfig' => 1,
                'expectedQueueConfig' => [
                    'stomp' => [
                        'host' => 'custom_host',
                        'port' => '61617',
                        'user' => 'custom_user',
                        'password' => 'custom_password',
                    ],
                    'default_connection' => 'stomp',
                ],
            ],
            'custom and relationship configurations exist without merge' => [
                'customQueueConfig' => [
                    'stomp' => [
                        'host' => 'custom_host',
                        'port' => '61617',
                        'user' => 'custom_user',
                        'password' => 'custom_password',
                    ],
                    'default_connection' => 'stomp',
                ],
                'activeMqServiceConfig' => [
                    'host' => 'activemq-artemis',
                    'port' => '61616',
                    'username' => 'admin',
                    'password' => 'admin',
                ],
                'isGreaterOrEqualReturns' => false,
                'consumersWaitMaxMessages' => false,
                'countCallGetConfig' => 1,
                'expectedQueueConfig' => [
                    'stomp' => [
                        'host' => 'custom_host',
                        'port' => '61617',
                        'user' => 'custom_user',
                        'password' => 'custom_password',
                    ],
                    'default_connection' => 'stomp',
                ],
            ],
            'custom and relationship configurations exist with merge' => [
                'customQueueConfig' => [
                    'stomp' => [
                        'user' => 'custom_user',
                        'password' => 'custom_password',
                    ],
                    '_merge' => true,
                ],
                'activeMqServiceConfig' => [
                    'host' => 'activemq-artemis',
                    'port' => '61616',
                    'username' => 'admin',
                    'password' => 'admin',
                ],
                'isGreaterOrEqualReturns' => false,
                'consumersWaitMaxMessages' => false,
                'countCallGetConfig' => 1,
                'expectedQueueConfig' => [
                    'stomp' => [
                        'host' => 'activemq-artemis',
                        'port' => '61616',
                        'user' => 'custom_user',
                        'password' => 'custom_password',
                    ],
                    'default_connection' => 'stomp',
                ],
            ],
            'only relationship configuration exists' => [
                'customQueueConfig' => [],
                'activeMqServiceConfig' => [
                    'host' => 'activemq-artemis',
                    'port' => '61616',
                    'username' => 'admin',
                    'password' => 'admin',
                ],
                'isGreaterOrEqualReturns' => true,
                'consumersWaitMaxMessages' => true,
                'countCallGetConfig' => 2,
                'expectedQueueConfig' => [
                    'stomp' => [
                        'host' => 'activemq-artemis',
                        'port' => '61616',
                        'user' => 'admin',
                        'password' => 'admin',
                    ],
                    'default_connection' => 'stomp',
                    'consumers_wait_for_messages' => 1,
                ],
            ],
            'relationship configuration missing port falls back to default STOMP port' => [
                'customQueueConfig' => [],
                'activeMqServiceConfig' => [
                    'host' => 'activemq-artemis',
                    'username' => 'admin',
                    'password' => 'admin',
                ],
                'isGreaterOrEqualReturns' => false,
                'consumersWaitMaxMessages' => false,
                'countCallGetConfig' => 1,
                'expectedQueueConfig' => [
                    'stomp' => [
                        'host' => 'activemq-artemis',
                        'port' => '61616',
                        'user' => 'admin',
                        'password' => 'admin',
                    ],
                    'default_connection' => 'stomp',
                ],
            ],
            'relationship configuration missing password does not raise a type error' => [
                'customQueueConfig' => [],
                'activeMqServiceConfig' => [
                    'host' => 'activemq-artemis',
                    'port' => '61616',
                    'username' => 'admin',
                ],
                'isGreaterOrEqualReturns' => false,
                'consumersWaitMaxMessages' => false,
                'countCallGetConfig' => 1,
                'expectedQueueConfig' => [
                    'stomp' => [
                        'host' => 'activemq-artemis',
                        'port' => '61616',
                        'user' => 'admin',
                        'password' => '',
                    ],
                    'default_connection' => 'stomp',
                ],
            ],
        ];
    }

    /**
     * @param array $config
     * @param bool  $expected
     * @return void
     *
     * @dataProvider isStompEnabledDataProvider
     */
    #[DataProvider('isStompEnabledDataProvider')]
    #[AllowMockObjectsWithoutExpectations]
    public function testIsStompEnabled(array $config, bool $expected): void
    {
        $this->activeMq->expects($this->once())
            ->method('getConfiguration')
            ->willReturn($config);

        $this->assertEquals($expected, $this->config->isStompEnabled());
    }

    /**
     * @return array
     */
    public static function isStompEnabledDataProvider(): array
    {
        return [
            'ActiveMQ relationship present' => [
                ['host' => 'activemq-artemis', 'port' => '61616'],
                true,
            ],
            'no ActiveMQ relationship' => [
                [],
                false,
            ],
        ];
    }
}
