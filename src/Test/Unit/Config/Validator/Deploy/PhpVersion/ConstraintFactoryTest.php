<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Unit\Config\Validator\Deploy\PhpVersion;

use Composer\Semver\Constraint\Constraint;
use Composer\Semver\Constraint\MultiConstraint;
use Magento\MagentoCloud\Config\Validator\Deploy\PhpVersion\ConstraintFactory;
use PHPUnit\Framework\TestCase;
use Magento\MagentoCloud\App\ContainerInterface;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * @inheritdoc
 */
class ConstraintFactoryTest extends TestCase
{
    /**
     * @var ContainerInterface|MockObject
     */
    private $containerMock;

    /**
     * @var ConstraintFactory
     */
    private $constraintFactory;

    /**
     * @inheritdoc
     */
    public function setUp()
    {
        $this->containerMock = $this->getMockForAbstractClass(ContainerInterface::class);
        $this->constraintFactory = new ConstraintFactory($this->containerMock);
    }

    public function testConstraint()
    {
        $operator = '==';
        $version = '4.5.6.0';
        $this->containerMock->expects($this->once())
            ->method('create')
            ->with(Constraint::class, [$operator,$version])
            ->willReturn(new Constraint($operator, $version));
        $this->constraintFactory->constraint($operator, $version);
    }

    public function testMulticonstraint()
    {
        $constraints = [
            $this->createMock(Constraint::class),
            $this->createMock(Constraint::class),
        ];
        $this->containerMock->expects($this->once())
            ->method('create')
            ->with(MultiConstraint::class, [$constraints])
            ->willReturn(new MultiConstraint($constraints));
        $this->constraintFactory->multiconstraint($constraints);
    }
}
