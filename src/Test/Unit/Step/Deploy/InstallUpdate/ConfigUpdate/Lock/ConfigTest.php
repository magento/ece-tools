<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Test\Unit\Step\Deploy\InstallUpdate\ConfigUpdate\Lock;

use Magento\MagentoCloud\Config\Environment;
use Magento\MagentoCloud\Config\Stage\DeployInterface;
use Magento\MagentoCloud\Step\Deploy\InstallUpdate\ConfigUpdate\Lock\Config;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @inheritdoc
 */
#[AllowMockObjectsWithoutExpectations]
class ConfigTest extends TestCase
{
    /**
     * @var Config
     */
    private $config;

    /**
     * @var Environment|MockObject
     */
    private $environmentMock;

    /**
     * @var DeployInterface|MockObject
     */
    private $stageConfigMock;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->environmentMock = $this->createMock(Environment::class);
        $this->stageConfigMock = $this->createMock(DeployInterface::class);

        $this->config = new Config($this->environmentMock, $this->stageConfigMock);
    }

    /**
     * Test get method.
     *
     * @param string $lockPath
     * @param string $lockProvider
     * @param array $expectedResult
     * @dataProvider getDataProvider
     * @return void
     */
    #[DataProvider('getDataProvider')]
    public function testGet($lockPath, $lockProvider, array $expectedResult): void
    {
        $this->environmentMock->expects($this->once())
            ->method('getEnv')
            ->with('MAGENTO_CLOUD_LOCKS_DIR')
            ->willReturn($lockPath);
        $this->stageConfigMock->method('get')
            ->with(DeployInterface::VAR_LOCK_PROVIDER)
            ->willReturn($lockProvider);

        $this->assertSame($expectedResult, $this->config->get());
    }

    /**
     * Data provider for getDataProvider method.
     *
     * @return array
     */
    public static function getDataProvider(): array
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
