<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Shell;

use Magento\MagentoCloud\App\ContainerInterface;
use Symfony\Component\Process\Process;

class ResultFactory
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
     * @param Process $process
     * @return ResultInterface
     */
    public function create(Process $process): ResultInterface
    {
        return $this->container->create(Result::class, ['process' => $process]);
    }
}
