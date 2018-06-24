<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Util\ForkManager;

use Magento\MagentoCloud\Util\ForkManager;
use Magento\MagentoCloud\App\ContainerInterface;

/**
 * Using a SingletonFactory for FactoryManager because we only want one a single manager to manage all child processes.
 */
class SingletonFactory
{
    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * @var ForkManager $object
     */
    private static $object = null;

    /**
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * @return  ForkManager
     */
    public function create()
    {
        if (null === static::$object) {
            static::$object = $this->container->get(ForkManager::class);
        }
        return static::$object;
    }
}
