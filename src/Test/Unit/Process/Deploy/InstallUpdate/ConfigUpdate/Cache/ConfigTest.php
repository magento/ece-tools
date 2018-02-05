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
            $this->stageConfigMock
        );
    }

    public function testGetWithValidEnvConfig()
    {
        $this->stageConfigMock->expects($this->once())
            ->method('get')
            ->with(DeployInterface::VAR_CACHE_CONFIGURATION)
            ->willReturn(['frontend' => ['cache_option' => 'value']]);
        $this->environmentMock->expects($this->never())
            ->method('getRelationship')
            ->with('redis');

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

    public function testGetWithRedisAndNotValidEnvConfig()
    {
        $this->stageConfigMock->expects($this->once())
            ->method('get')
            ->with(DeployInterface::VAR_CACHE_CONFIGURATION)
            ->willReturn([]);
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
                'frontend' => [
                    'default' => [
                        'backend' => 'Cm_Cache_Backend_Redis',
                        'backend_options' => [
                            'server' => 'redis_host',
                            'port' => '1234',
                            'database' => 1
                        ],
                    ],
                    'page_cache' => [
                        'backend' => 'Cm_Cache_Backend_Redis',
                        'backend_options' => [
                            'server' => 'redis_host',
                            'port' => '1234',
                            'database' => 1
                        ],
                    ],
                ]
            ],
            $this->config->get()
        );
    }
}
