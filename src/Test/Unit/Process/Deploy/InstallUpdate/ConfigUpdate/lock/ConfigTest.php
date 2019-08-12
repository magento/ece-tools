<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Unit\Process\Deploy\InstallUpdate\ConfigUpdate\Lock;

use Magento\MagentoCloud\Process\Deploy\InstallUpdate\ConfigUpdate\Lock\Config;
use Magento\MagentoCloud\Config\Environment;
use Magento\MagentoCloud\Config\Stage\DeployInterface;
use PHPUnit\Framework\TestCase;
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

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        $this->environmentMock = $this->createMock(Environment::class);
        $this->stageConfigMock = $this->getMockForAbstractClass(DeployInterface::class);
        $this->config = new Config($this->environmentMock, $this->stageConfigMock);
    }

    /**
     * @param $lockPath
     * @param string $lockProvider
     * @param array $expectedResult
     * @dataProvider getDataProvider
     */
    public function testGet($lockPath, $lockProvider, array $expectedResult)
    {
        $this->environmentMock->expects($this->once())
            ->method('getEnv')
            ->with('MAGENTO_CLOUD_LOCKS_DIR')
            ->willReturn($lockPath);
        $this->stageConfigMock->expects($this->any())
            ->method('get')
            ->with(DeployInterface::VAR_LOCK_PROVIDER)
            ->willReturn($lockProvider);
        $this->assertSame($expectedResult, $this->config->get());
    }

    /**
     * @return array
     */
    public function getDataProvider(): array
    {
        return [
            'There is MAGENTO_CLOUD_LOCKS_DIR and LOCK_PROVIDER is file' => [
                'lockPath' => '/tmp/locks',
                'lockProvider' => 'file',
                'expectedResult' => [
                    'provider' => 'file',
                    'config' => [
                        'path' => '/tmp/locks',
                    ],
                ],
            ],
            'There is MAGENTO_CLOUD_LOCKS_DIR and LOCK_PROVIDER is db' => [
                'lockPath' => '/tmp/locks',
                'lockProvider' => 'db',
                'expectedResult' => [
                    'provider' => 'db',
                    'config' => [
                        'prefix' => null,
                    ],
                ],
            ],
            'There is no MAGENTO_CLOUD_LOCKS_DIR and LOCK_PROVIDER is file' => [
                'lockPath' => null,
                'lockProvider' => 'file',
                'expectedResult' => [
                    'provider' => 'db',
                    'config' => [
                        'prefix' => null,
                    ],
                ],
            ],
            'There is no MAGENTO_CLOUD_LOCKS_DIR and LOCK_PROVIDER is db' => [
                'lockPath' => null,
                'lockProvider' => 'db',
                'expectedResult' => [
                    'provider' => 'db',
                    'config' => [
                        'prefix' => null,
                    ],
                ],
            ],
        ];
    }
}
