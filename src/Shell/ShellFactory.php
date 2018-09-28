<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Shell;

use Magento\MagentoCloud\App\ContainerInterface;

/**
 * Factory class for shell wrappers.
 */
class ShellFactory
{
    const STRATEGY_SHELL = 'shell';
    const STRATEGY_MAGENTO_SHELL = 'magento_shell';

    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * @param string $strategy
     * @return ShellInterface
     */
    public function create(string $strategy): ShellInterface
    {
        if ($strategy === self::STRATEGY_MAGENTO_SHELL) {
            return $this->createMagento();
        }

        return $this->container->create(Shell::class);
    }

    /**
     * @return MagentoShell
     */
    public function createMagento(): MagentoShell
    {
        return $this->container->create(MagentoShell::class);
    }
}
