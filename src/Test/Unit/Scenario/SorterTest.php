<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Test\Unit\Scenario;

use Magento\MagentoCloud\Scenario\Sorter;
use PHPUnit\Framework\TestCase;

/**
 * @inheritDoc
 */
class SorterTest extends TestCase
{
    /**
     * @inheritDoc
     */
    public function testSortScenario()
    {
        $scenarios = [
            [
                'name' => 'step1',
                'type' => 'some-type',
                'priority' => 1000,
                'arguments' => []
            ],
            [
                'name' => 'step2',
                'type' => 'some-type',
                'priority' => 900,
                'arguments' => []
            ],
            [
                'name' => 'step3',
                'type' => 'some-type',
                'priority' => 1200,
                'arguments' => []
            ],
            [
                'name' => 'step4',
                'type' => 'some-type',
                'priority' => 100,
                'arguments' => []
            ],
        ];

        $sorter = new Sorter();
        $sorter->sortScenarios($scenarios);

        $this->assertEquals(
            ['step4', 'step2', 'step1', 'step3'],
            array_column($scenarios, 'name')
        );
    }

    /**
     * @inheritDoc
     */
    public function testSortScenarioSteps()
    {
        $scenarios = [
            [
                'name' => 'step1',
                'type' => 'some-type',
                'priority' => 1000,
                'arguments' => [
                    [
                        'name' => 'steps',
                        'items' => [
                            [
                                'name' => 'substep4',
                                'type' => 'some-type',
                                'priority' => 1300,
                            ],
                            [
                                'name' => 'substep1',
                                'type' => 'some-type',
                                'priority' => 1000,
                            ],
                            [
                                'name' => 'substep2',
                                'type' => 'some-type',
                                'priority' => 1200,
                            ],
                            [
                                'name' => 'substep3',
                                'type' => 'some-type',
                                'priority' => 1100,
                            ],
                        ],
                    ],
                ],
            ],
            [
                'name' => 'step2',
                'type' => 'some-type',
                'priority' => 100,
                'arguments' => []
            ],
        ];

        $sorter = new Sorter();
        $sorter->sortScenarios($scenarios);

        $this->assertEquals(
            ['step2', 'step1'],
            array_column($scenarios, 'name')
        );

        $this->assertEquals(
            ['substep1', 'substep3', 'substep2', 'substep4'],
            array_column($scenarios[0]['arguments'][0]['items'], 'name')
        );
    }

    /**
     * @inheritDoc
     */
    public function testSortScenarioValidators()
    {
        $validators = [
            300 => [
                'items' => [
                    [
                        'name' => 'validator4',
                        'type' => 'some-type',
                        'priority' => 1300,
                    ],
                    [
                        'name' => 'validator1',
                        'type' => 'some-type',
                        'priority' => 1000,
                    ],
                    [
                        'name' => 'validator2',
                        'type' => 'some-type',
                        'priority' => 1200,
                    ],
                    [
                        'name' => 'validator3',
                        'type' => 'some-type',
                        'priority' => 1100,
                    ],
                ],
            ],
        ];

        $scenarios = [
            [
                'name' => 'step1',
                'type' => 'some-type',
                'priority' => 1000,
                'arguments' => [
                    [
                        'name' => 'validators',
                        'items' => $validators
                    ],
                ],
            ],
        ];

        $sorter = new Sorter();
        $sorter->sortScenarios($scenarios);

        $this->assertEquals(
            ['validator1', 'validator3', 'validator2', 'validator4'],
            array_column($scenarios[0]['arguments'][0]['items'][300]['items'], 'name')
        );
    }
}
