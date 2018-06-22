<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Util\ForkManager;

use Magento\MagentoCloud\Util\ForkManager;
use Magento\MagentoCloud\App\ContainerInterface;

class SingletonFactory
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

    private static $object = null;

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