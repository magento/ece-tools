<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Test\Unit\Scenario;

use Magento\MagentoCloud\Scenario\Collector\Scenario;
use Magento\MagentoCloud\Scenario\Collector\Step;
use Magento\MagentoCloud\Scenario\Collector\Action;
use Magento\MagentoCloud\Scenario\Exception\ValidationException;
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
     * @var Action|MockObject
     */
    private $actionCollectorMock;

    /**
     * @var Scenario|MockObject
     */
    private $scenarioCollectorMock;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        $this->resolverMock = $this->createMock(Resolver::class);
        $this->stepCollectorMock = $this->createMock(Step::class);
        $this->actionCollectorMock = $this->createMock(Action::class);
        $this->scenarioCollectorMock = $this->createMock(Scenario::class);

        $this->merger = new Merger(
            $this->resolverMock,
            $this->stepCollectorMock,
            $this->actionCollectorMock,
            $this->scenarioCollectorMock
        );
    }

    /**
     * @throws \Magento\MagentoCloud\Scenario\Exception\ValidationException
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
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
        $action = [
            '@name' => 'create-deploy-failed-flag',
            '@priority' => 100,
            '@type' => 'Magento\MagentoCloud\OnFail\Action\CreateDeployFailedFlag',
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
                    'onFail' => [
                        'action' => $action,
                    ]
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
        $this->actionCollectorMock->expects($this->once())
            ->method('collect')
            ->with($action)
            ->willReturn(
                [
                    'name' => 'create-deploy-failed-flag',
                    'priority' => 100,
                    'type' => 'Magento\MagentoCloud\OnFail\Action\CreateDeployFailedFlag',
                ]
            );

        $expectedResult = [
            'steps' => [
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
            ],
            'actions' => [
                'create-deploy-failed-flag' => [
                    'name' => 'create-deploy-failed-flag',
                    'priority' => 100,
                    'type' => 'Magento\MagentoCloud\OnFail\Action\CreateDeployFailedFlag',
                ],
            ],
        ];

        $this->resolverMock->expects($this->once())
            ->method('resolve')
            ->with($expectedResult);

        $this->merger->merge($scenarios);
    }

    public function testMissedArgumentPriorityException()
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Argument(s) "@priority" are missed from item');

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

    public function testMissedArgumentNameException()
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Argument(s) "@name" are missed from item');

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

    public function testNoStepsInScenarioException()
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Steps aren\'t exist in "scenario1.xml" file');

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
