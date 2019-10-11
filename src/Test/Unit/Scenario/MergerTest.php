<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Test\Unit\Scenario;

use Magento\MagentoCloud\Scenario\Collector\Scenario;
use Magento\MagentoCloud\Scenario\Collector\Step;
use Magento\MagentoCloud\Scenario\Merger;
use Magento\MagentoCloud\Scenario\Resolver;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @inheritDoc
 */
class MergerTest extends TestCase
{
    /**
     * @var Merger
     */
    private $merger;

    /**
     * @var Resolver|MockObject
     */
    private $resolverMock;

    /**
     * @var Step|MockObject
     */
    private $stepCollectorMock;

    /**
     * @var Scenario|MockObject
     */
    private $scenarioCollectorMock;

    /**
     * @inheritDoc
     */
    protected function setUp()
    {
        $this->resolverMock = $this->createMock(Resolver::class);
        $this->stepCollectorMock = $this->createMock(Step::class);
        $this->scenarioCollectorMock = $this->createMock(Scenario::class);

        $this->merger = new Merger($this->resolverMock, $this->stepCollectorMock, $this->scenarioCollectorMock);
    }

    /**
     * @throws \Magento\MagentoCloud\Scenario\Exception\ValidationException
     */
    public function testMerge()
    {
        $scenarios = ['scenario1.xml', 'scenario2.xml'];
        $step1 = [
            '@name' => 'clear-init-directory',
            '@priority' => 100,
            '@type' => 'Magento\MagentoCloud\Step\Build\ClearInitDirectory',
        ];
        $step2 = [
            '@name' => 'compress-static-content',
            '@priority' => 200,
            '@type' => 'Magento\MagentoCloud\Step\Build\CompressStaticContent',
        ];
        $step1Over = [
            '@name' => 'clear-init-directory',
            '@priority' => 300,
            '@type' => 'customType',
        ];

        $this->scenarioCollectorMock->expects($this->exactly(2))
            ->method('collect')
            ->withConsecutive(['scenario1.xml'], ['scenario2.xml'])
            ->willReturnOnConsecutiveCalls(
                [
                    '@xmlns:xsi' => 'http://www.w3.org/2001/XMLSchema-instance',
                    '@xsi:noNamespaceSchemaLocation' => '../../config/scenario.xsd',
                    'step' => [
                        $step1,
                        $step2
                    ],
                ],
                [
                    '@xmlns:xsi' => 'http://www.w3.org/2001/XMLSchema-instance',
                    '@xsi:noNamespaceSchemaLocation' => '../../config/scenario.xsd',
                    'step' => [
                        $step1Over,
                    ],
                ]
            );
        $this->stepCollectorMock->expects($this->exactly(3))
            ->method('collect')
            ->withConsecutive([$step1], [$step2], [$step1Over])
            ->willReturnOnConsecutiveCalls(
                [
                    'name' => 'clear-init-directory',
                    'priority' => 100,
                    'type' => 'Magento\MagentoCloud\Step\Build\ClearInitDirectory',
                ],
                [
                    'name' => 'compress-static-content',
                    'priority' => 200,
                    'type' => 'Magento\MagentoCloud\Step\Build\CompressStaticContent',
                ],
                [
                    'name' => 'clear-init-directory',
                    'priority' => 300,
                    'type' => 'customType',
                ]
            );

        $expectedResult = [
            'clear-init-directory' => [
                'name' => 'clear-init-directory',
                'priority' => 300,
                'type' => 'customType',
            ],
            'compress-static-content' => [
                'name' => 'compress-static-content',
                'priority' => 200,
                'type' => 'Magento\MagentoCloud\Step\Build\CompressStaticContent',
            ],
        ];

        $this->resolverMock->expects($this->once())
            ->method('resolve')
            ->with($expectedResult);

        $this->merger->merge($scenarios);
    }

    /**
     * @expectedException \Magento\MagentoCloud\Scenario\Exception\ValidationException
     * @expectedExceptionMessage Argument(s) "@priority" are missed from step
     */
    public function testMissedArgumentPriorityException()
    {
        $this->scenarioCollectorMock->expects($this->once())
            ->method('collect')
            ->with('scenario1.xml')
            ->willReturn([
                '@xmlns:xsi' => 'http://www.w3.org/2001/XMLSchema-instance',
                '@xsi:noNamespaceSchemaLocation' => '../../config/scenario.xsd',
                'step' => [
                    [

                        '@name' => 'clear-init-directory',
                        '@type' => 'Magento\MagentoCloud\Step\Build\ClearInitDirectory',
                        '#' => '',
                    ],
                ],
            ]);

        $this->merger->merge(['scenario1.xml']);
    }

    /**
     * @expectedException \Magento\MagentoCloud\Scenario\Exception\ValidationException
     * @expectedExceptionMessage Argument(s) "@name" are missed from step
     */
    public function testMissedArgumentNameException()
    {
        $this->scenarioCollectorMock->expects($this->once())
            ->method('collect')
            ->with('scenario1.xml')
            ->willReturn([
                '@xmlns:xsi' => 'http://www.w3.org/2001/XMLSchema-instance',
                '@xsi:noNamespaceSchemaLocation' => '../../config/scenario.xsd',
                'step' => [
                    [
                        '@priority' => '100',
                        '@type' => 'Magento\MagentoCloud\Step\Build\ClearInitDirectory',
                        '#' => '',
                    ],
                ],
            ]);

        $this->merger->merge(['scenario1.xml']);
    }

    /**
     * @expectedException \Magento\MagentoCloud\Scenario\Exception\ValidationException
     * @expectedExceptionMessage Steps aren't exist in "scenario1.xml" file
     */
    public function testNoStepsInScenarioException()
    {
        $this->scenarioCollectorMock->expects($this->once())
            ->method('collect')
            ->with('scenario1.xml')
            ->willReturn([
                '@xmlns:xsi' => 'http://www.w3.org/2001/XMLSchema-instance',
                '@xsi:noNamespaceSchemaLocation' => '../../config/scenario.xsd',
            ]);

        $this->merger->merge(['scenario1.xml']);
    }
}
