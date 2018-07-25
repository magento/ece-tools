<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Docker\Service;

use Magento\MagentoCloud\App\ContainerInterface;

/**
 * Create instance of Docker service configuration.
 */
class ServiceFactory
{
    const SERVICE_VARNISH = 'varnish';
    const SERVICE_REDIS = 'redis';

    /**
     * @var array
     */
    private static $map = [
        self::SERVICE_VARNISH => VarnishService::class,
        self::SERVICE_REDIS => RedisService::class,
    ];

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
     * @param string $name
     * @return ServiceInterface
     */
    public function create(string $name): ServiceInterface
    {
        return $this->container->create(self::$map[$name]);
    }
}
