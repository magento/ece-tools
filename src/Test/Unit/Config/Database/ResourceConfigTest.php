<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Test\Unit\Config\Database;

use Magento\MagentoCloud\Config\Database\DbConfig;
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
     * @var DbConfig|MockObject
     */
    private $dbConfigMock;

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
    protected function setUp(): void
    {
        $this->stageConfigMock = $this->getMockForAbstractClass(DeployInterface::class);
        $this->dbConfigMock = $this->createMock(DbConfig::class);
        $this->resourceConfig = new ResourceConfig(
            $this->dbConfigMock,
            $this->stageConfigMock,
            new ConfigMerger()
        );
    }

    /**
     * @param array $stageConfig
     * @param array $dbConfig
     * @param $expectedResult
     * @dataProvider getDataProvider
     */
    public function testGet($stageConfig, $dbConfig, $expectedResult)
    {
        $this->stageConfigMock->expects($this->once())
            ->method('get')
            ->with(DeployInterface::VAR_RESOURCE_CONFIGURATION)
            ->willReturn($stageConfig);
        $this->dbConfigMock->expects($this->any())
            ->method('get')
            ->willReturn($dbConfig);
        $this->assertSame($expectedResult, $this->resourceConfig->get());
    }

    /**
     * Data provider for testGet
     *
     * @return array
     *
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function getDataProvider(): array
    {
        return [
            'default resource config' => [
                'stageConfig' => [],
                'dbConfig' => [
                    'connection' => [
                        'default' => [],
                        'indexer' => [],
                    ]
                ],
                'expectedResult' => [
                    'default_setup' => [
                        'connection' => 'default',
                    ],
                ],
            ],
            'default resource config with merge' => [
                'stageConfig' => ['_merge' => true],
                'dbConfig' => [
                    'connection' => [
                        'default' => [],
                        'indexer' => [],
                    ]
                ],
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
                'dbConfig' => [
                    'connection' => [
                        'default' => [],
                        'indexer' => [],
                    ]
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
                'dbConfig' => [
                    'connection' => [
                        'default' => [],
                        'indexer' => [],
                    ]
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
            'with available split db ' => [
                'stageConfig' => [],
                'dbConfig' => [
                    'connection' => [
                        'default' => [],
                        'indexer' => [],
                        'checkout' => [],
                        'sales' => [],
                    ]
                ],
                'expectedResult' => [
                    'default_setup' => [
                        'connection' => 'default',
                    ],
                    'checkout' => [
                        'connection' => 'checkout',
                    ],
                    'sales' => [
                        'connection' => 'sales',
                    ],
                ],
            ],
            'with custom resource config for split connections with merge ' => [
                'stageConfig' => [
                    '_merge' => true,
                    'checkout' => [
                        'connection' => 'checkout',
                    ],
                    'sales' => [
                        'connection' => 'sales',
                    ],
                ],
                'dbConfig' => [
                    'connection' => [
                        'default' => [],
                        'indexer' => [],
                    ]
                ],
                'expectedResult' => [
                    'default_setup' => [
                        'connection' => 'default',
                    ],
                ],
            ],
            'with custom resource config for split connections without merge ' => [
                'stageConfig' => [
                    '_merge' => false,
                    'checkout' => [
                        'connection' => 'checkout',
                    ],
                    'sales' => [
                        'connection' => 'sales',
                    ],
                ],
                'dbConfig' => [
                    'connection' => [
                        'default' => [],
                        'indexer' => [],
                    ]
                ],
                'expectedResult' => [
                    'default_setup' => [
                        'connection' => 'default',
                    ],
                ],
            ],
        ];
    }
}
