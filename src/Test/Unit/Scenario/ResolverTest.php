<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Test\Unit\Scenario;

use Magento\MagentoCloud\App\ContainerInterface;
use Magento\MagentoCloud\Step\StepInterface;
use Magento\MagentoCloud\Scenario\Exception\ValidationException;
use Magento\MagentoCloud\Scenario\Merger;
use Magento\MagentoCloud\Scenario\Resolver;
use Magento\MagentoCloud\Shell\ShellInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @inheritDoc
 */
class ResolverTest extends TestCase
{
    /**
     * @var Resolver
     */
    private $resolver;

    /**
     * @var ContainerInterface|MockObject
     */
    private $containerMock;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        $this->containerMock = $this->getMockForAbstractClass(ContainerInterface::class);

        $this->resolver = new Resolver(
            $this->containerMock
        );
    }

    /**
     * @throws ValidationException
     */
    public function testResolve(): void
    {
        $scenarios = [
            [
                'name' => 'step1',
                'type' => StepInterface::class,
                'arguments' => [
                    'arg1' => [
                        'name' => 'arg1',
                        'xsi:type' => Merger::XSI_TYPE_STRING,
                        '#' => 'Some string',
                    ],
                    'arg2' => [
                        'name' => 'arg2',
                        'xsi:type' => Merger::XSI_TYPE_OBJECT,
                        '#' => ShellInterface::class
                    ],
                    'arg3' => [
                        'name' => 'arg3',
                        'xsi:type' => Merger::XSI_TYPE_ARRAY,
                        'items' => [
                            'arg31' => [
                                'name' => 'arg31',
                                'xsi:type' => Merger::XSI_TYPE_STRING,
                                '#' => 'Some string 2'
                            ]
                        ]
                    ]
                ]
            ]
        ];

        $step1Mock = $this->getMockForAbstractClass(StepInterface::class);
        $arg2Mock = $this->getMockForAbstractClass(ShellInterface::class);

        $instances = [
            'step1' => $step1Mock
        ];

        $this->containerMock->method('create')
            ->willReturnMap([
                [
                    StepInterface::class,
                    ['arg1' => 'Some string', 'arg2' => $arg2Mock, 'arg3' => ['arg31' => 'Some string 2']],
                    $step1Mock
                ],
                [ShellInterface::class, [], $arg2Mock]
            ]);

        $this->assertSame(
            $instances,
            $this->resolver->resolve($scenarios)
        );
    }

    /**
     * @throws ValidationException
     */
    public function testResolveEmpty(): void
    {
        $this->assertSame(
            [],
            $this->resolver->resolve([])
        );
    }

    /**
     * @throws ValidationException
     * @expectedException \Magento\MagentoCloud\Scenario\Exception\ValidationException
     * @expectedExceptionMessage Unknown xsi:type "Some type"
     */
    public function testResolveWrongXsiType(): void
    {
        $scenarios = [
            [
                'name' => 'step1',
                'type' => StepInterface::class,
                'arguments' => [
                    'arg1' => [
                        'name' => 'arg1',
                        'xsi:type' => 'Some type',
                        '#' => 'Some string',
                    ],
                ]
            ]
        ];

        $step1Mock = $this->getMockForAbstractClass(StepInterface::class);

        $instances = [
            'step1' => $step1Mock
        ];

        $this->assertSame(
            $instances,
            $this->resolver->resolve($scenarios)
        );
    }

    /**
     * @throws ValidationException
     * @expectedException \Magento\MagentoCloud\Scenario\Exception\ValidationException
     * @expectedExceptionMessage Empty parameter name
     */
    public function testResolveWrongName(): void
    {
        $scenarios = [
            [
                'name' => 'step1',
                'type' => StepInterface::class,
                'arguments' => [
                    'arg1' => [
                        'xsi:type' => Merger::XSI_TYPE_STRING,
                        '#' => 'Some string',
                    ],
                ]
            ]
        ];

        $step1Mock = $this->getMockForAbstractClass(StepInterface::class);

        $instances = [
            'step1' => $step1Mock
        ];

        $this->assertSame(
            $instances,
            $this->resolver->resolve($scenarios)
        );
    }

    /**
     * @throws ValidationException
     * @expectedException \Magento\MagentoCloud\Scenario\Exception\ValidationException
     * @expectedExceptionMessage is not instance of
     */
    public function testResolveWrongStepType(): void
    {
        $scenarios = [
            [
                'name' => 'step1',
                'type' => ShellInterface::class,
                'arguments' => []
            ]
        ];

        $step1Mock = $this->getMockForAbstractClass(ShellInterface::class);

        $instances = [
            'step1' => $step1Mock
        ];

        $this->containerMock->method('create')
            ->willReturnMap([
                [ShellInterface::class, [], $step1Mock]
            ]);

        $this->assertSame(
            $instances,
            $this->resolver->resolve($scenarios)
        );
    }
}
