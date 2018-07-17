<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Patch;

use Magento\MagentoCloud\App\ContainerInterface;
use Magento\MagentoCloud\Config\Environment;

/**
 * Creates instances of ApplierInterface.
 * It will choose the correct one based on the current environment.
 */
class ApplierFactory
{
    const APPLIER_VARIABLE_NAME = 'APPLIER';
    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * @var Environment
     */
    private $environment;

    /**
     * @param ContainerInterface $container
     * @param Environment $environment
     */
    public function __construct(ContainerInterface $container, Environment $environment)
    {
        $this->container = $container;
        $this->environment = $environment;
    }

    /**
     * Creates instances of ApplierInterface.
     *
     * @return ApplierInterface
     */
    public function create(): ApplierInterface
    {
        switch ($this->environment->get(static::APPLIER_VARIABLE_NAME)) {
            case 'QUILT':
                return $this->container->create(QuiltApplier::class);
            default:
                return $this->container->create(GitApplier::class);
        }
    }
}
