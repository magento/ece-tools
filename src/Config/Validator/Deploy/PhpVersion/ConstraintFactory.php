<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Config\Validator\Deploy\PhpVersion;

use Composer\Semver\Constraint\Constraint;
use Composer\Semver\Constraint\ConstraintInterface;
use Composer\Semver\Constraint\MultiConstraint;
use Composer\Semver\VersionParser;
use Magento\MagentoCloud\App\ContainerInterface;

/**
 * Creates instance of ConstraintInterface object
 */
class ConstraintFactory
{
    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * @var VersionParser
     */
    private $versionParser;

    /**
     * @param ContainerInterface $container
     * @param VersionParser $versionParser
     */
    public function __construct(ContainerInterface $container, VersionParser $versionParser)
    {
        $this->container = $container;
        $this->versionParser = $versionParser;
    }

    /**
     * @param string $operator
     * @param string $version
     * @return Constraint
     */
    public function constraint(string $operator, string $version): Constraint
    {
        return $this->container->create(Constraint::class, [
            'operator' => $operator,
            'version' => $version
        ]);
    }

    /**
     * @param ConstraintInterface[] $constraints
     * @return MultiConstraint
     */
    public function multiconstraint(array $constraints): MultiConstraint
    {
        return $this->container->create(MultiConstraint::class, [
            'constraints' => $constraints
        ]);
    }

    /**
     * Returns the current PHP constraint
     *
     * @return Constraint
     */
    public function getCurrentPhpConstraint(): Constraint
    {
        return $this->constraint('==', PHP_VERSION);
    }
}
