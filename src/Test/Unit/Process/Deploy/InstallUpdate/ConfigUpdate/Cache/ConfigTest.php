<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Unit\Process\Deploy\InstallUpdate\ConfigUpdate\Cache;

use Magento\MagentoCloud\Config\Stage\DeployInterface;
use PHPUnit\Framework\TestCase;
use Magento\MagentoCloud\Config\Environment;
use Magento\MagentoCloud\Process\Deploy\InstallUpdate\ConfigUpdate\Cache\Config;
use PHPUnit_Framework_MockObject_MockObject as Mock;
use Psr\Log\LoggerInterface;

/**
 * @inheritdoc
 */
class ConfigTest extends TestCase
{
    /**
     * @var Environment|Mock
     */
    private $environmentMock;

    /**
     * @var DeployInterface|Mock
     */
    private $stageConfigMock;

    /**
     * @var LoggerInterface|Mock
     */
    private $loggerMock;

    /**
     * @var Config
     */
    private $config;

    protected function setUp()
    {
        $this->environmentMock = $this->createMock(Environment::class);
        $this->stageConfigMock = $this->getMockForAbstractClass(DeployInterface::class);
        $this->loggerMock = $this->getMockBuilder(LoggerInterface::class)
            ->getMockForAbstractClass();

        $this->config = new Config(
            $this->environmentMock,
            $this->stageConfigMock,
            $this->loggerMock
        );
    }

    public function testGetWithValidEnvConfig()
    {
        $this->stageConfigMock->expects($this->exactly(2))
            ->method('get')
            ->willReturnMap([
                [
                    DeployInterface::VAR_CACHE_CONFIGURATION,
                    ['frontend' => ['cache_option' => 'value']]
                ],
                [
                    DeployInterface::VAR_REDIS_USE_SLAVE_CONNECTION,
                    false
                ]
            ]);
        $this->environmentMock->expects($this->never())
            ->method('getRelationship')
            ->with('redis');

        $this->loggerMock->expects($this->never())
            ->method('notice');

        $this->assertEquals(
            ['frontend' => ['cache_option' => 'value']],
            $this->config->get()
        );
    }

    public function testGetWithValidEnvConfigWithEnabledRedisSlave()
    {
        $this->stageConfigMock->expects($this->exactly(2))
            ->method('get')
            ->willReturnMap([
                [
                    DeployInterface::VAR_CACHE_CONFIGURATION,
                    ['frontend' => ['cache_option' => 'value']]
                ],
                [
                    DeployInterface::VAR_REDIS_USE_SLAVE_CONNECTION,
                    true
                ]
            ]);
        $this->environmentMock->expects($this->never())
            ->method('getRelationship')
            ->with('redis');

        $this->loggerMock->expects($this->once())
            ->method('notice')
            ->with('The variable \'' . DeployInterface::VAR_REDIS_USE_SLAVE_CONNECTION . '\' is ignored'
                    . ' as you set your own cache connection in \'' . DeployInterface::VAR_CACHE_CONFIGURATION . '\'');

        $this->assertEquals(
            ['frontend' => ['cache_option' => 'value']],
            $this->config->get()
        );
    }

    public function testGetWithoutRedisAndWithNotValidEnvConfig()
    {
        $this->stageConfigMock->expects($this->once())
            ->method('get')
            ->with(DeployInterface::VAR_CACHE_CONFIGURATION)
            ->willReturn([]);
        $this->environmentMock->expects($this->once())
            ->method('getRelationship')
            ->with('redis')
            ->willReturn([]);

        $this->assertEmpty($this->config->get());
    }

    /**
     * @param $masterConnection array
     * @param $slaveConnection array
     * @param $useSlave boolean
     * @param $expectedResult array
     *
     * @dataProvider getFromRelationshipsDataProvider
     */
    public function testGetFromRelationships($masterConnection, $slaveConnection, $useSlave, $expectedResult)
    {
        $this->stageConfigMock->expects($this->exactly(2))
            ->method('get')
            ->willReturnMap([
                [
                    DeployInterface::VAR_CACHE_CONFIGURATION,
                    []
                ],
                [
                    DeployInterface::VAR_REDIS_USE_SLAVE_CONNECTION,
                    $useSlave
                ]
            ]);
        $this->environmentMock->expects($this->exactly(2))
            ->method('getRelationship')
            ->willReturnMap([
                ['redis', $masterConnection],
                ['redis-slave', $slaveConnection],

            ]);

        $this->assertEquals(
            $expectedResult,
            $this->config->get()
        );
    }

    /**
     * Data provider for testGetFromRelationships.
     *
     * Results value for next data:
     * 1 - data for 'redis' relationships
     * 2 - data for 'redis-slave' relationships
     * 3 - value for REDIS_USE_SLAVE_CONNECTION variable
     * 4 - expected result
     *
     * @return array
     */
    public function getFromRelationshipsDataProvider()
    {
        $relationshipsRedis = [
            [
                'host' => 'master.host',
                'port' => 'master.port',
                'scheme' => 'redis',
            ]
        ];
        $relationshipsRedisSlave = [
            [
                'host' => 'slave.host',
                'port' => 'slave.port',
                'scheme' => 'redis',
            ]
        ];

        $resultMasterOnlyConnection = [
            'frontend' => [
                'default' => [
                    'backend' => 'Cm_Cache_Backend_Redis',
                    'backend_options' => [
                        'server' => 'master.host',
                        'port' => 'master.port',
                        'database' => 1
                    ],
                ],
                'page_cache' => [
                    'backend' => 'Cm_Cache_Backend_Redis',
                    'backend_options' => [
                        'server' => 'master.host',
                        'port' => 'master.port',
                        'database' => 1
                    ],
                ],
            ]
        ];
        $resultMasterSlaveConnection = $resultMasterOnlyConnection;
        $resultMasterSlaveConnection['frontend']['default']['backend_options']['load_from_slave'] = [
            'server' => 'slave.host',
            'port' => 'slave.port',
        ];
        $resultMasterSlaveConnection['frontend']['page_cache']['backend_options']['load_from_slave'] = [
            'server' => 'slave.host',
            'port' => 'slave.port',
        ];

        return [
            [
                $relationshipsRedis,
                [],
                false,
                $resultMasterOnlyConnection
            ],
            [
                $relationshipsRedis,
                $relationshipsRedisSlave,
                false,
                $resultMasterOnlyConnection
            ],
            [
                $relationshipsRedis,
                [],
                true,
                $resultMasterOnlyConnection
            ],
            [
                $relationshipsRedis,
                $relationshipsRedisSlave,
                true,
                $resultMasterSlaveConnection
            ],
        ];
    }
}
