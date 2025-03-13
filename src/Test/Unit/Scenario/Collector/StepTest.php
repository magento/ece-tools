<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Test\Unit\Scenario\Collector;

use Magento\MagentoCloud\Scenario\Collector\Step;
use Magento\MagentoCloud\Scenario\Exception\ValidationException;
use PHPUnit\Framework\TestCase;

/**
 * @inheritDoc
 */
class StepTest extends TestCase
{
    /**
     * @var Step
     */
    private $stepCollector;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        $this->stepCollector = new Step();
    }

    public function testMissedArgumentException()
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Argument(s) "@xsi:type" are missed from argument in step "backup-data"');

        $step = [
            '@name' => 'backup-data',
            '@type' => 'Magento\MagentoCloud\Step\Build\BackupData',
            '@priority' => 300,
            'arguments' => [
                'argument' => [
                    [
                        '@name' => 'logger',
                        '#' => 'Psr\Log\LoggerInterface',
                    ],
                    [
                        '@name' => 'steps',
                        '@xsi:type' => '[',
                        'item' => []
                    ]
                ],
            ],
        ];

        $this->stepCollector->collect($step);
    }

    public function testWrongArgumentTypeException()
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('xsi:type "wrong-type" not allowed in argument "logger"');

        $step = [
            '@name' => 'backup-data',
            '@type' => 'Magento\MagentoCloud\Step\Build\BackupData',
            '@priority' => 300,
            'arguments' => [
                'argument' => [
                    [
                        '@name' => 'logger',
                        '@xsi:type' => 'wrong-type',
                        '#' => 'Psr\Log\LoggerInterface',
                    ],
                    [
                        '@name' => 'steps',
                        '@xsi:type' => '[',
                        'item' => []
                    ]
                ],
            ],
        ];

        $this->stepCollector->collect($step);
    }

    public function testMissedItemArgumentsException()
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Argument(s) "@name" are missed from item');

        $step = [
            '@name' => 'backup-data',
            '@type' => 'Magento\MagentoCloud\Step\Build\BackupData',
            '@priority' => 300,
            'arguments' => [
                'argument' => [
                    [
                        '@name' => 'logger',
                        '@xsi:type' => 'object',
                        '#' => 'Psr\Log\LoggerInterface',
                    ],
                    [
                        '@name' => 'validator',
                        '@xsi:type' => 'array',
                        'item' => [
                            [
                                '@xsi:type' => 'object',
                                '#' => 'Magento\MagentoCloud\Step\Build\BackupData\StaticContent',
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $this->stepCollector->collect($step);
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     * @throws \Magento\MagentoCloud\Scenario\Exception\ValidationException
     */
    public function testCollect()
    {
        $step = [
            '@name' => 'backup-data',
            '@type' => 'Magento\MagentoCloud\Step\Build\BackupData',
            '@priority' => 300,
            'arguments' => [
                'argument' => [
                    [
                        '@name' => 'logger',
                        '@xsi:type' => 'object',
                        '#' => 'Psr\Log\LoggerInterface',
                    ],
                    [
                        '@name' => 'steps',
                        '@xsi:type' => 'array',
                        'item' => [
                            [
                                '@name' => 'static-content',
                                '@xsi:type' => 'object',
                                '@priority' => 100,
                                '#' => 'Magento\MagentoCloud\Step\Build\BackupData\StaticContent',
                            ],
                            [
                                '@name' => 'non-static-content',
                                '@xsi:type' => 'object',
                                '@priority' => 200,
                                '#' => 'Magento\MagentoCloud\Step\Build\BackupData\StaticContent',
                            ],
                            [
                                '@name' => '500',
                                '@xsi:type' => 'array',
                                '@priority' => 300,
                                'item' => [
                                    [
                                        '@name' => 'sub-item',
                                        '@xsi:type' => 'object',
                                        '@priority' => 100,
                                        '#' => 'SubItemObject',
                                    ],
                                    [
                                        '@name' => 'sub-item2',
                                        '@xsi:type' => 'object',
                                        '@priority' => 200,
                                        '@skip' => 'true',
                                        '#' => 'SubItemObject2',
                                    ]
                                ]
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $expectedResult = [
            'name' => 'backup-data',
            'type' => 'Magento\MagentoCloud\Step\Build\BackupData',
            'skip' => false,
            'arguments' => [
                [
                    'name' => 'logger',
                    'xsi:type' => 'object',
                    '#' => 'Psr\Log\LoggerInterface',
                ],
                [
                    'name' => 'steps',
                    'xsi:type' => 'array',
                    'items' => [
                        'static-content' => [
                            'name' => 'static-content',
                            'xsi:type' => 'object',
                            '#' => 'Magento\MagentoCloud\Step\Build\BackupData\StaticContent',
                            'priority' => 100,
                            'skip' => false,
                        ],
                        'non-static-content' => [
                            'name' => 'non-static-content',
                            'xsi:type' => 'object',
                            '#' => 'Magento\MagentoCloud\Step\Build\BackupData\StaticContent',
                            'priority' => 200,
                            'skip' => false,
                        ],
                        500 => [
                            'name' => '500',
                            'xsi:type' => 'array',
                            'priority' => 300,
                            'skip' => false,
                            'items' => [
                                'sub-item' => [
                                    'name' => 'sub-item',
                                    'xsi:type' => 'object',
                                    'priority' => 100,
                                    '#' => 'SubItemObject',
                                    'skip' => false,
                                ],
                                'sub-item2' => [
                                    'name' => 'sub-item2',
                                    'xsi:type' => 'object',
                                    'priority' => 200,
                                    '#' => 'SubItemObject2',
                                    'skip' => true,
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            'priority' => 300,
        ];

        $this->assertEquals(
            $expectedResult,
            $this->stepCollector->collect($step)
        );
    }
}
