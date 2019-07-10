<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Shell;

use Magento\MagentoCloud\App\ContainerInterface;

/**
 * Creates instance of ProcessInterface
 */
class ProcessFactory
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
     * Creates instance of Process
     *
     * @param array $params
     * @return Process|ProcessInterface
     * @throws \RuntimeException if Process can't be created
     */
    public function create(array $params): ProcessInterface
    {
        return $this->container->create(Process::class, $params);
    }
}
