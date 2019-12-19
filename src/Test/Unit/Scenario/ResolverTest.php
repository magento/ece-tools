<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Test\Unit\Scenario;

use Magento\MagentoCloud\App\ContainerInterface;
use Magento\MagentoCloud\Scenario\Collector\Step;
use Magento\MagentoCloud\Scenario\Sorter;
use Magento\MagentoCloud\Step\SkipStep;
use Magento\MagentoCloud\Step\StepInterface;
use Magento\MagentoCloud\Scenario\Exception\ValidationException;
use Magento\MagentoCloud\Scenario\Resolver;
use Magento\MagentoCloud\Shell\ShellInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

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
     * @var Sorter|MockObject
     */
    private $sorterMock;

    /**
     * @var LoggerInterface|MockObject
     */
    private $loggerMock;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        $this->containerMock = $this->getMockForAbstractClass(ContainerInterface::class);
        $this->sorterMock = $this->createMock(Sorter::class);
        $this->loggerMock = $this->createMock(LoggerInterface::class);

        $this->resolver = new Resolver(
            $this->containerMock,
            $this->sorterMock
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
                'skip' => false,
                'arguments' => [
                    'arg1' => [
                        'name' => 'arg1',
                        'xsi:type' => Step::XSI_TYPE_STRING,
                        '#' => 'Some string',
                    ],
                    'arg2' => [
                        'name' => 'arg2',
                        'xsi:type' => Step::XSI_TYPE_OBJECT,
                        '#' => ShellInterface::class
                    ],
                    'arg3' => [
                        'name' => 'arg3',
                        'xsi:type' => Step::XSI_TYPE_ARRAY,
                        'items' => [
                            'arg31' => [
                                'name' => 'arg31',
                                'xsi:type' => Step::XSI_TYPE_STRING,
                                '#' => 'Some string 2',
                                'skip' => false,
                            ],
                            'arg32' => [
                                'name' => 'arg32',
                                'xsi:type' => Step::XSI_TYPE_OBJECT,
                                '#' => 'Object',
                                'skip' => true,
                            ],
                        ]
                    ]
                ]
            ],
            [
                'name' => 'step2',
                'type' => 'someType',
                'arguments' => [],
                'skip' => true,
            ]
        ];

        $step1Mock = $this->getMockForAbstractClass(StepInterface::class);
        $skipStepMock = $this->getMockForAbstractClass(StepInterface::class);
        $arg2Mock = $this->getMockForAbstractClass(ShellInterface::class);

        $instances = [
            'step1' => $step1Mock,
            'step2' => $skipStepMock
        ];

        $this->containerMock->method('get')
            ->with(LoggerInterface::class)
            ->willReturn($this->loggerMock);

        $this->containerMock->method('create')
            ->willReturnMap([
                [
                    StepInterface::class,
                    [
                        'arg1' => 'Some string',
                        'arg2' => $arg2Mock,
                        'arg3' => ['arg31' => 'Some string 2', 'arg32' => $skipStepMock]
                    ],
                    $step1Mock
                ],
                [
                    ShellInterface::class,
                    [],
                    $arg2Mock
                ],
                [
                    SkipStep::class,
                    [$this->loggerMock, 'arg32'],
                    $skipStepMock
                ],
                [
                    SkipStep::class,
                    [$this->loggerMock, 'step2'],
                    $skipStepMock
                ]
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
     */
    public function testResolveWrongXsiType(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Unknown xsi:type "Some type');

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
                ],
                'skip' => false,
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
     */
    public function testResolveWrongName(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Empty parameter name in step "step1"');

        $scenarios = [
            [
                'name' => 'step1',
                'type' => StepInterface::class,
                'arguments' => [
                    'arg1' => [
                        'xsi:type' => Step::XSI_TYPE_STRING,
                        '#' => 'Some string',
                    ],
                ],
                'skip' => false,
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
     */
    public function testResolveWrongStepType(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('is not instance of');

        $scenarios = [
            [
                'name' => 'step1',
                'type' => ShellInterface::class,
                'arguments' => [],
                'skip' => false,
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
