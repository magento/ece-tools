<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Patch;

use Illuminate\Contracts\Config\Repository;
use Magento\MagentoCloud\App\Container;
use Psr\Container\ContainerInterface;

/**
 * Creates instances of ApplierInterface.
 * It will choose the correct one based on the current environment.
 */
class ApplierFactory
{
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
     * Creates instances of ApplierInterface.
     *
     * @return ApplierInterface
     */
    public function create(): ApplierInterface
    {
        if (isQuiltInstalled) {
            return $this->container->create(QuiltApplier::class);
        }
        return $this->container->create(GitApplier::class);
    }

    private function isQuiltInstalled() : bool
    {
        return !!shell_exec('which quilt');
    }
}
