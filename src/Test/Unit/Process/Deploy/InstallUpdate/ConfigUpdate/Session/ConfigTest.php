<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Unit\Process\Deploy\InstallUpdate\ConfigUpdate\Session;

use Composer\Package\PackageInterface;
use Composer\Semver\Comparator;
use Magento\MagentoCloud\Config\ConfigMerger;
use Magento\MagentoCloud\Config\Environment;
use Magento\MagentoCloud\Config\Stage\DeployInterface;
use Magento\MagentoCloud\Config\StageConfigInterface;
use Magento\MagentoCloud\Package\Manager;
use Magento\MagentoCloud\Process\Deploy\InstallUpdate\ConfigUpdate\Session\Config;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @inheritdoc
 */
class ConfigTest extends TestCase
{
    /**
     * @var Environment|MockObject
     */
    private $environmentMock;

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
     * @var Config
     */
    private $config;

    protected function setUp()
    {
        $this->environmentMock = $this->createMock(Environment::class);
        $this->stageConfigMock = $this->getMockForAbstractClass(DeployInterface::class);
        $this->configMergerMock = $this->createTestProxy(ConfigMerger::class);
        $this->managerMock = $this->createMock(Manager::class);
        $this->comparatorMock = new Comparator();

        $this->config = new Config(
            $this->environmentMock,
            $this->stageConfigMock,
            $this->configMergerMock,
            $this->managerMock,
            $this->comparatorMock
        );
    }

    public function testGetWithValidEnvConfig()
    {
        $this->stageConfigMock->expects($this->once())
            ->method('get')
            ->with(DeployInterface::VAR_SESSION_CONFIGURATION)
            ->willReturn(['save' => 'some_storage']);
        $this->environmentMock->expects($this->never())
            ->method('getRelationship')
            ->with('redis');

        $this->assertEquals(
            ['save' => 'some_storage'],
            $this->config->get()
        );
    }

    /**
     * @param array $envSessionConfiguration
     * @param array $relationships
     * @param array $expected
     * @dataProvider envConfigurationMergingDataProvider
     */
    public function testEnvConfigurationMerging(
        array $envSessionConfiguration,
        array $relationships,
        array $expected
    ) {
        $this->stageConfigMock->expects($this->once())
            ->method('get')
            ->with(DeployInterface::VAR_SESSION_CONFIGURATION)
            ->willReturn($envSessionConfiguration);
        $this->environmentMock->expects($this->once())
            ->method('getRelationship')
            ->with('redis')
            ->willReturn($relationships);
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
        $relationships = [
            [
                'host' => 'host',
                'port' => 'port',
                'scheme' => 'redis',
            ],
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
                $relationships,
                $result,
            ],
            [
                [StageConfigInterface::OPTION_MERGE => true],
                $relationships,
                $result,
            ],
            [
                [
                    StageConfigInterface::OPTION_MERGE => true,
                    'key' => 'value',
                ],
                $relationships,
                $resultWithMergedKey,
            ],
            [
                [
                    StageConfigInterface::OPTION_MERGE => true,
                    'redis' => [
                        'host' => 'new_host',
                        'port' => 'new_port',
                    ],
                ],
                $relationships,
                $resultWithMergedHostAndPort,
            ],
        ];
    }

    /**
     * @param array $envSessionConfiguration
     * @param array $relationships
     * @param array $expected
     * @dataProvider envConfigurationMergingWithPrevVersionDataProvider
     */
    public function testEnvConfigurationMergingWithPrevVersion(
        array $envSessionConfiguration,
        array $relationships,
        array $expected
    ) {
        $this->stageConfigMock->expects($this->once())
            ->method('get')
            ->with(DeployInterface::VAR_SESSION_CONFIGURATION)
            ->willReturn($envSessionConfiguration);
        $this->environmentMock->expects($this->once())
            ->method('getRelationship')
            ->with('redis')
            ->willReturn($relationships);
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
        $relationships = [
            [
                'host' => 'host',
                'port' => 'port',
                'scheme' => 'redis',
            ],
        ];

        $result = [
            'save' => 'redis',
            'redis' => [
                'host' => 'host',
                'port' => 'port',
                'database' => Config::REDIS_DATABASE_SESSION,
                'disable_locking' => 0
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
                $relationships,
                $result,
            ],
            [
                [StageConfigInterface::OPTION_MERGE => true],
                $relationships,
                $result,
            ],
            [
                [
                    StageConfigInterface::OPTION_MERGE => true,
                    'key' => 'value',
                ],
                $relationships,
                $resultWithMergedKey,
            ],
            [
                [
                    StageConfigInterface::OPTION_MERGE => true,
                    'redis' => [
                        'host' => 'new_host',
                        'port' => 'new_port',
                    ],
                ],
                $relationships,
                $resultWithMergedHostAndPort,
            ],
        ];
    }
}
