<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Unit\Process\Deploy\InstallUpdate\ConfigUpdate\Session;

use Magento\MagentoCloud\Config\Stage\DeployInterface;
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
            $this->stageConfigMock
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

    /**
     * @param array $redisConfig
     * @param bool $isLockingDisabled
     * @param array $expected
     * @dataProvider getWithRedisDataProvider
     */
    public function testGetWithRedisAndNotValidEnvConfig(array $redisConfig, bool $isLockingDisabled, array $expected)
    {
        $this->stageConfigMock->expects($this->exactly(2))
            ->method('get')
            ->withConsecutive(
                [DeployInterface::VAR_SESSION_CONFIGURATION],
                [DeployInterface::VAR_REDIS_SESSION_DISABLE_LOCKING]
            )
            ->willReturn(
                ['some_key' => 'some_storage'],
                $isLockingDisabled
            );
        $this->environmentMock->expects($this->once())
            ->method('getRelationship')
            ->with('redis')
            ->willReturn($redisConfig);

        $this->assertEquals(
            $expected,
            $this->config->get()
        );
    }

    public function getWithRedisDataProvider()
    {
        return [
            [
                [
                    [
                        'host' => 'redis_host',
                        'port' => '1234'
                    ]
                ],
                false,
                [
                    'save' => 'redis',
                    'redis' => [
                        'host' => 'redis_host',
                        'port' => '1234',
                        'database' => 0,
                        'disable_locking' => 0
                    ]
                ]
            ],
            [
                [
                    [
                        'host' => 'redis_host',
                        'port' => '1234'
                    ]
                ],
                true,
                [
                    'save' => 'redis',
                    'redis' => [
                        'host' => 'redis_host',
                        'port' => '1234',
                        'database' => 0,
                        'disable_locking' => 1
                    ]
                ]
            ],
        ];
    }
}
