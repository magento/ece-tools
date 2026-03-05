<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Test\Unit\Step\Deploy\InstallUpdate\ConfigUpdate\Session;

use Composer\Package\PackageInterface;
use Composer\Semver\Comparator;
use Magento\MagentoCloud\Config\ConfigException;
use Magento\MagentoCloud\Config\ConfigMerger;
use Magento\MagentoCloud\Config\Stage\DeployInterface;
use Magento\MagentoCloud\Config\StageConfigInterface;
use Magento\MagentoCloud\Package\Manager;
use Magento\MagentoCloud\Service\Redis;
use Magento\MagentoCloud\Service\RedisSession;
use Magento\MagentoCloud\Service\Valkey;
use Magento\MagentoCloud\Service\ValkeySession;
use Magento\MagentoCloud\Step\Deploy\InstallUpdate\ConfigUpdate\Session\Config;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * @inheritdoc
 * @SuppressWarnings("CouplingBetweenObjects")
 */
#[AllowMockObjectsWithoutExpectations]
class ConfigTest extends TestCase
{
    /**
     * @var Redis|MockObject
     */
    private $redisMock;

    /**
     * @var Valkey|MockObject
     */
    private $valkeyMock;

    /**
     * @var RedisSession|MockObject
     */
    private $redisSessionMock;

    /**
     * @var ValkeySession|MockObject
     */
    private $valkeySessionMock;

    /**
     * @var DeployInterface|MockObject
     */
    private $stageConfigMock;

    /**
     * @var ConfigMerger
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
     *
     * @throws Exception
     */
    protected function setUp(): void
    {
        $this->redisMock = $this->createMock(Redis::class);
        $this->redisSessionMock = $this->createMock(RedisSession::class);
        $this->valkeyMock = $this->createMock(Valkey::class);
        $this->valkeySessionMock = $this->createMock(ValkeySession::class);
        $this->stageConfigMock = $this->createMock(DeployInterface::class);
        $this->loggerMock = $this->createMock(LoggerInterface::class);
        $this->configMergerMock = new ConfigMerger();
        $this->managerMock = $this->createMock(Manager::class);
        $this->comparatorMock = new Comparator();

        $this->config = new Config(
            $this->redisMock,
            $this->redisSessionMock,
            $this->valkeyMock,
            $this->valkeySessionMock,
            $this->stageConfigMock,
            $this->configMergerMock,
            $this->managerMock,
            $this->comparatorMock,
            $this->loggerMock
        );
    }

    /**
     * Test get method with valid env config.
     *
     * @return void
     * @throws ConfigException
     */
    public function testGetWithValidEnvConfig()
    {
        $this->stageConfigMock->expects($this->once())
            ->method('get')
            ->with(DeployInterface::VAR_SESSION_CONFIGURATION)
            ->willReturn(['save' => 'some_storage']);
        $this->redisMock->expects($this->never())
            ->method('getConfiguration');
        $this->valkeyMock->expects($this->never())
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
     * Test env configuration merging.
     *
     * @param array $envSessionConfiguration
     * @param array $redisSessionConfig
     * @param array $redisConfig
     * @param int $redisCallTime
     * @param array $expected
     * @param string $expectedLogMessage
     * @dataProvider envConfigurationMergingDataProvider
     * @return void
     * @throws ConfigException
     * @throws Exception
     */
    #[DataProvider('envConfigurationMergingDataProvider')]
    public function testEnvConfigurationMerging(
        array $envSessionConfiguration,
        array $redisSessionConfig,
        array $redisConfig,
        int $redisCallTime,
        array $expected,
        string $expectedLogMessage
    ): void {
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
        $package = $this->createMock(PackageInterface::class);
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
     * Test env configuration merging valkey.
     *
     * @param array $envSessionConfiguration
     * @param array $valkeySessionConfig
     * @param array $valkeyConfig
     * @param int $valkeyCallTime
     * @param array $expected
     * @param string $expectedLogMessage
     * @dataProvider envConfigurationMergingDataProviderValkey
     * @return void
     * @throws ConfigException
     * @throws Exception
     */
    #[DataProvider('envConfigurationMergingDataProviderValkey')]
    public function testEnvConfigurationMergingValkey(
        array $envSessionConfiguration,
        array $valkeySessionConfig,
        array $valkeyConfig,
        int $valkeyCallTime,
        array $expected,
        string $expectedLogMessage
    ): void {
        $this->loggerMock->expects($this->once())
            ->method('info')
            ->with($expectedLogMessage);
        $this->stageConfigMock->expects($this->once())
            ->method('get')
            ->with(DeployInterface::VAR_SESSION_CONFIGURATION)
            ->willReturn($envSessionConfiguration);
        $this->valkeyMock->expects($this->exactly($valkeyCallTime))
            ->method('getConfiguration')
            ->willReturn($valkeyConfig);
        $this->valkeySessionMock->expects($this->once())
            ->method('getConfiguration')
            ->willReturn($valkeySessionConfig);
        $package = $this->createMock(PackageInterface::class);
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
     * Data provider for envConfigurationMergingDataProviderValkey method.
     *
     * @return array
     */
    public static function envConfigurationMergingDataProviderValkey(): array
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
                'database' => Config::CACHE_DATABASE_SESSION,
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
                'valkey will be used for session if it was not override by SESSION_CONFIGURATION',
            ],
            [
                [StageConfigInterface::OPTION_MERGE => true],
                [],
                $redisConfig,
                1,
                $result,
                'valkey will be used for session if it was not override by SESSION_CONFIGURATION',
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
                'valkey will be used for session if it was not override by SESSION_CONFIGURATION',
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
                'valkey-session will be used for session if it was not override by SESSION_CONFIGURATION',
            ],
        ];
    }

    /**
     * Data provider for envConfigurationMergingDataProvider method.
     *
     * @return array
     */
    public static function envConfigurationMergingDataProvider(): array
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
                'database' => Config::CACHE_DATABASE_SESSION,
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
     * Test env configuration merging with previous version.
     *
     * @param array $envSessionConfiguration
     * @param array $redisSessionConfig
     * @param array $redisConfig
     * @param int $redisCallTime
     * @param array $expected
     * @param string $expectedLogMessage
     * @dataProvider envConfigurationMergingWithPrevVersionDataProvider
     * @return void
     * @throws ConfigException
     * @throws Exception
     */
    #[DataProvider('envConfigurationMergingWithPrevVersionDataProvider')]
    public function testEnvConfigurationMergingWithPrevVersion(
        array $envSessionConfiguration,
        array $redisSessionConfig,
        array $redisConfig,
        int $redisCallTime,
        array $expected,
        string $expectedLogMessage
    ): void {
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
        $package = $this->createMock(PackageInterface::class);
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
     * Data provider for envConfigurationMergingWithPrevVersionDataProvider method.
     *
     * @return array
     */
    public static function envConfigurationMergingWithPrevVersionDataProvider(): array
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
                'database' => Config::CACHE_DATABASE_SESSION,
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
