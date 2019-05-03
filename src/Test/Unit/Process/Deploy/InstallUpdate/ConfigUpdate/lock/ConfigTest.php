<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Unit\Process\Deploy\InstallUpdate\ConfigUpdate\Lock;

use Magento\MagentoCloud\Process\Deploy\InstallUpdate\ConfigUpdate\Lock\Config;
use Magento\MagentoCloud\Config\Environment;
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
     * @var Config
     */
    private $config;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        $this->environmentMock = $this->createMock(Environment::class);
        $this->config = new Config($this->environmentMock);
    }

    /**
     * @param $lockPath
     * @param array $expectedResult
     * @dataProvider getDataProvider
     */
    public function testGet($lockPath, array $expectedResult)
    {
        $this->environmentMock->expects($this->once())
            ->method('getEnv')
            ->with('MAGENTO_CLOUD_LOCKS_DIR')
            ->willReturn($lockPath);
        $this->assertSame($expectedResult, $this->config->get());
    }

    /**
     * @return array
     */
    public function getDataProvider(): array
    {
        return [
            'There is MAGENTO_CLOUD_LOCKS_DIR' => [
                'lockPath' => '/tmp/locks',
                'expectedResult' => [
                    'provider' => 'file',
                    'config' => [
                        'path' => '/tmp/locks',
                    ],
                ],
            ],
            'There is no MAGENTO_CLOUD_LOCKS_DIR' => [
                'lockPath' => null,
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
