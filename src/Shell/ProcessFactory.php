<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Shell;

use Magento\MagentoCloud\App\ContainerInterface;
use Symfony\Component\Process\Process;

/**
 * Creates instance of Process
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
     * @return Process
     */
    public function create(array $params): Process
    {
        return $this->container->create(Process::class, $params);
    }
}
