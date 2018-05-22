<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Unit\Process\Deploy\InstallUpdate\ConfigUpdate\Session;

use Magento\MagentoCloud\Config\ConfigMerger;
use Magento\MagentoCloud\Config\Stage\DeployInterface;
use Magento\MagentoCloud\Config\StageConfigInterface;
use PHPUnit\Framework\TestCase;
use Magento\MagentoCloud\Config\Environment;
use Magento\MagentoCloud\Process\Deploy\InstallUpdate\ConfigUpdate\Session\Config;
use PHPUnit_Framework_MockObject_MockObject as Mock;

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
     * @var Config
     */
    private $config;

    protected function setUp()
    {
        $this->environmentMock = $this->createMock(Environment::class);
        $this->stageConfigMock = $this->getMockForAbstractClass(DeployInterface::class);

        $this->config = new Config(
            $this->environmentMock,
            $this->stageConfigMock,
            new ConfigMerger()
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

    public function testGetWithoutRedisAndWithNotValidEnvConfig()
    {
        $this->stageConfigMock->expects($this->once())
            ->method('get')
            ->with(DeployInterface::VAR_SESSION_CONFIGURATION)
            ->willReturn(['some_key' => 'some_storage']);
        $this->environmentMock->expects($this->once())
            ->method('getRelationship')
            ->with('redis')
            ->willReturn([]);

        $this->assertEmpty($this->config->get());
    }

    public function testGetWithRedisAndNotValidEnvConfig()
    {
        $this->stageConfigMock->expects($this->once())
            ->method('get')
            ->with(DeployInterface::VAR_SESSION_CONFIGURATION)
            ->willReturn(['some_key' => 'some_storage']);
        $this->environmentMock->expects($this->once())
            ->method('getRelationship')
            ->with('redis')
            ->willReturn([
                [
                    'host' => 'redis_host',
                    'port' => '1234'
                ]
            ]);

        $this->assertEquals(
            [
                'save' => 'redis',
                'redis' => [
                    'host' => 'redis_host',
                    'port' => '1234',
                    'database' => 0
                ]
            ],
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
                'database' => 0,
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
            [
                [
                    StageConfigInterface::OPTION_MERGE => false,
                    'redis' => [
                        'host' => 'new_host',
                        'port' => 'new_port',
                    ],
                ],
                $relationships,
                $result,
            ],
        ];
    }
}
