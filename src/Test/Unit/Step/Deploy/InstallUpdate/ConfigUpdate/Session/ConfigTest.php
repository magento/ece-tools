<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Test\Unit\Step\Deploy\InstallUpdate\ConfigUpdate\Session;

use Composer\Package\PackageInterface;
use Composer\Semver\Comparator;
use Magento\MagentoCloud\Config\ConfigMerger;
use Magento\MagentoCloud\Config\Stage\DeployInterface;
use Magento\MagentoCloud\Config\StageConfigInterface;
use Magento\MagentoCloud\Package\Manager;
use Magento\MagentoCloud\Service\RedisSession;
use Magento\MagentoCloud\Step\Deploy\InstallUpdate\ConfigUpdate\Session\Config;
use Magento\MagentoCloud\Service\Redis;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * @inheritdoc
 */
class ConfigTest extends TestCase
{
    /**
     * @var Redis|MockObject
     */
    private $redisMock;

    /**
     * @var RedisSession|MockObject
     */
    private $redisSessionMock;

    /**
     * @var DeployInterface|MockObject
     */
    private $stageConfigMock;

    /**
     * @var ConfigMerger|MockObject
     */
    private $configMergerMock;

    /**
     * @var Manager|MockObject
     */
    private $managerMock;

    /**
     * @var Comparator|MockObject
     */
    private $comparatorMock;

    /**
     * @var LoggerInterface|MockObject
     */
    private $loggerMock;

    /**
     * @var Config
     */
    private $config;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->redisMock = $this->createMock(Redis::class);
        $this->redisSessionMock = $this->createMock(RedisSession::class);
        $this->stageConfigMock = $this->getMockForAbstractClass(DeployInterface::class);
        $this->loggerMock = $this->getMockForAbstractClass(LoggerInterface::class);
        $this->configMergerMock = $this->createTestProxy(ConfigMerger::class);
        $this->managerMock = $this->createMock(Manager::class);
        $this->comparatorMock = new Comparator();

        $this->config = new Config(
            $this->redisMock,
            $this->redisSessionMock,
            $this->stageConfigMock,
            $this->configMergerMock,
            $this->managerMock,
            $this->comparatorMock,
            $this->loggerMock
        );
    }

    public function testGetWithValidEnvConfig()
    {
        $this->stageConfigMock->expects($this->once())
            ->method('get')
            ->with(DeployInterface::VAR_SESSION_CONFIGURATION)
            ->willReturn(['save' => 'some_storage']);
        $this->redisMock->expects($this->never())
            ->method('getConfiguration');
        $this->redisSessionMock->expects($this->never())
            ->method('getConfiguration');
        $this->loggerMock->expects($this->never())
            ->method('info');

        $this->assertEquals(
            ['save' => 'some_storage'],
            $this->config->get()
        );
    }

    /**
     * @param array $envSessionConfiguration
     * @param array $redisSessionConfig
     * @param array $redisConfig
     * @param int $redisCallTime
     * @param array $expected
     * @param string $expectedLogMessage
     * @dataProvider envConfigurationMergingDataProvider
     */
    public function testEnvConfigurationMerging(
        array $envSessionConfiguration,
        array $redisSessionConfig,
        array $redisConfig,
        int $redisCallTime,
        array $expected,
        string $expectedLogMessage
    ) {
        $this->loggerMock->expects($this->once())
            ->method('info')
            ->with($expectedLogMessage);
        $this->stageConfigMock->expects($this->once())
            ->method('get')
            ->with(DeployInterface::VAR_SESSION_CONFIGURATION)
            ->willReturn($envSessionConfiguration);
        $this->redisMock->expects($this->exactly($redisCallTime))
            ->method('getConfiguration')
            ->willReturn($redisConfig);
        $this->redisSessionMock->expects($this->once())
            ->method('getConfiguration')
            ->willReturn($redisSessionConfig);
        $package = $this->getMockForAbstractClass(PackageInterface::class);
        $this->managerMock->expects($this->once())
            ->method('get')
            ->with('colinmollenhour/php-redis-session-abstract')
            ->willReturn($package);
        $package->expects($this->once())
            ->method('getVersion')
            ->willReturn('1.3.4');

        $this->assertEquals(
            $expected,
            $this->config->get()
        );
    }

    /**
     * @return array
     */
    public function envConfigurationMergingDataProvider(): array
    {
        $redisConfig = [
            'host' => 'host',
            'port' => 'port',
            'scheme' => 'redis',
        ];

        $result = [
            'save' => 'redis',
            'redis' => [
                'host' => 'host',
                'port' => 'port',
                'database' => Config::REDIS_DATABASE_SESSION,
                'disable_locking' => 1
            ],
        ];

        $resultWithMergedKey = $result;
        $resultWithMergedKey['key'] = 'value';

        $resultWithMergedHostAndPort = $result;
        $resultWithMergedHostAndPort['redis']['host'] = 'new_host';
        $resultWithMergedHostAndPort['redis']['port'] = 'new_port';

        return [
            [
                [],
                [],
                $redisConfig,
                1,
                $result,
                'redis will be used for session if it was not override by SESSION_CONFIGURATION',
            ],
            [
                [StageConfigInterface::OPTION_MERGE => true],
                [],
                $redisConfig,
                1,
                $result,
                'redis will be used for session if it was not override by SESSION_CONFIGURATION',
            ],
            [
                [
                    StageConfigInterface::OPTION_MERGE => true,
                    'key' => 'value',
                ],
                [],
                $redisConfig,
                1,
                $resultWithMergedKey,
                'redis will be used for session if it was not override by SESSION_CONFIGURATION',
            ],
            [
                [
                    StageConfigInterface::OPTION_MERGE => true,
                    'redis' => [
                        'host' => 'new_host',
                        'port' => 'new_port',
                    ],
                ],
                $redisConfig,
                $redisConfig,
                0,
                $resultWithMergedHostAndPort,
                'redis-session will be used for session if it was not override by SESSION_CONFIGURATION',
            ],
        ];
    }

    /**
     * @param array $envSessionConfiguration
     * @param array $redisSessionConfig
     * @param array $redisConfig
     * @param int $redisCallTime
     * @param array $expected
     * @param string $expectedLogMessage
     * @dataProvider envConfigurationMergingWithPrevVersionDataProvider
     */
    public function testEnvConfigurationMergingWithPrevVersion(
        array $envSessionConfiguration,
        array $redisSessionConfig,
        array $redisConfig,
        int $redisCallTime,
        array $expected,
        string $expectedLogMessage
    ) {
        $this->loggerMock->expects($this->once())
            ->method('info')
            ->with($expectedLogMessage);
        $this->stageConfigMock->expects($this->once())
            ->method('get')
            ->with(DeployInterface::VAR_SESSION_CONFIGURATION)
            ->willReturn($envSessionConfiguration);
        $this->redisMock->expects($this->exactly($redisCallTime))
            ->method('getConfiguration')
            ->willReturn($redisConfig);
        $this->redisSessionMock->expects($this->once())
            ->method('getConfiguration')
            ->willReturn($redisSessionConfig);
        $package = $this->getMockForAbstractClass(PackageInterface::class);
        $this->managerMock->expects($this->once())
            ->method('get')
            ->with('colinmollenhour/php-redis-session-abstract')
            ->willReturn($package);
        $package->expects($this->once())
            ->method('getVersion')
            ->willReturn('1.3.3');

        $this->assertEquals(
            $expected,
            $this->config->get()
        );
    }

    /**
     * @return array
     */
    public function envConfigurationMergingWithPrevVersionDataProvider(): array
    {
        $redisConfig = [
            'host' => 'host',
            'port' => 'port',
            'scheme' => 'redis',
            'password' => 'password'
        ];

        $result = [
            'save' => 'redis',
            'redis' => [
                'host' => 'host',
                'port' => 'port',
                'database' => Config::REDIS_DATABASE_SESSION,
                'disable_locking' => 0,
                'password' => 'password'
            ],
        ];

        $resultWithMergedKey = $result;
        $resultWithMergedKey['key'] = 'value';

        $resultWithMergedHostAndPort = $result;
        $resultWithMergedHostAndPort['redis']['host'] = 'new_host';
        $resultWithMergedHostAndPort['redis']['port'] = 'new_port';

        return [
            [
                [],
                [],
                $redisConfig,
                1,
                $result,
                'redis will be used for session if it was not override by SESSION_CONFIGURATION',
            ],
            [
                [StageConfigInterface::OPTION_MERGE => true],
                [],
                $redisConfig,
                1,
                $result,
                'redis will be used for session if it was not override by SESSION_CONFIGURATION',
            ],
            [
                [
                    StageConfigInterface::OPTION_MERGE => true,
                    'key' => 'value',
                ],
                [],
                $redisConfig,
                1,
                $resultWithMergedKey,
                'redis will be used for session if it was not override by SESSION_CONFIGURATION',
            ],
            [
                [
                    StageConfigInterface::OPTION_MERGE => true,
                    'redis' => [
                        'host' => 'new_host',
                        'port' => 'new_port',
                    ],
                ],
                $redisConfig,
                $redisConfig,
                0,
                $resultWithMergedHostAndPort,
                'redis-session will be used for session if it was not override by SESSION_CONFIGURATION',
            ],
        ];
    }
}
