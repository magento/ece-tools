<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Unit\Config\Database;

use Magento\MagentoCloud\Config\Stage\DeployInterface;
use Magento\MagentoCloud\Config\ConfigMerger;
use Magento\MagentoCloud\Config\Database\ResourceConfig;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @inheritdoc
 */
class ResourceConfigTest extends TestCase
{
    /**
     * @var DeployInterface|MockObject
     */
    private $stageConfigMock;

    /**
     * @var ResourceConfig
     */
    private $resourceConfig;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        $this->stageConfigMock = $this->getMockForAbstractClass(DeployInterface::class);
        $this->resourceConfig = new ResourceConfig($this->stageConfigMock, new ConfigMerger());
    }

    /**
     * @param $stageConfig
     * @param $expectedResult
     * @dataProvider getDataProvider
     */
    public function testGet($stageConfig, $expectedResult)
    {
        $this->stageConfigMock->expects($this->once())
            ->method('get')
            ->with(DeployInterface::VAR_RESOURCE_CONFIGURATION)
            ->willReturn($stageConfig);

        $this->assertSame($expectedResult, $this->resourceConfig->get());
    }

    /**
     * @return array
     */
    public function getDataProvider(): array
    {
        return [
            'default resource config' => [
                'stageConfig' => [],
                'expectedResult' => [
                    'default_setup' => [
                        'connection' => 'default',
                    ],
                ],
            ],
            'default resource config with merge' => [
                'stageConfig' => ['_merge' => true],
                'expectedResult' => [
                    'default_setup' => [
                        'connection' => 'default',
                    ],
                ],
            ],
            'overwrite resource config' => [
                'stageConfig' => [
                    'some_resource' => [
                        'connection' => 'some_connection',
                    ],
                ],
                'expectedResult' => [
                    'some_resource' => [
                        'connection' => 'some_connection',
                    ],
                ],
            ],
            'merge resource config' => [
                'stageConfig' => [
                    '_merge' => true,
                    'some_resource' => [
                        'connection' => 'some_connection',
                    ],
                ],
                'expectedResult' => [
                    'default_setup' => [
                        'connection' => 'default',
                    ],
                    'some_resource' => [
                        'connection' => 'some_connection',
                    ],
                ],
            ],
        ];
    }
}
