<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Service;

use Magento\MagentoCloud\App\ContainerInterface;

/**
 * Creates instance of ServiceInterface
 */
class ServiceFactory
{
    /**
     * @var array
     */
    private $serviceMap = [
        ServiceInterface::NAME_REDIS => Redis::class,
        ServiceInterface::NAME_ELASTICSEARCH => ElasticSearch::class,
        ServiceInterface::NAME_RABBITMQ => RabbitMQ::class,
        ServiceInterface::NAME_DB => Database::class,
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
     * Creates instance of ServiceInterface
     *
     * @param string $serviceName
     * @return ServiceInterface
     * @throws ConfigurationMismatchException when service isn't defined in service map
     */
    public function create(string $serviceName): ServiceInterface
    {
        if (!array_key_exists($serviceName, $this->serviceMap)) {
            throw new ConfigurationMismatchException(sprintf(
                'Service "%s" is not supported',
                $serviceName
            ));
        }

        return $this->container->create($serviceName);
    }
}
