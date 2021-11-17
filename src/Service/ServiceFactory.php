<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

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
        ServiceInterface::NAME_REDIS_SESSION => RedisSession::class,
        ServiceInterface::NAME_ELASTICSEARCH => ElasticSearch::class,
        ServiceInterface::NAME_OPENSEARCH => OpenSearch::class,
        ServiceInterface::NAME_RABBITMQ => RabbitMq::class,
        ServiceInterface::NAME_DB_MYSQL => Database::class,
        ServiceInterface::NAME_DB_MARIA => Database::class,
        ServiceInterface::NAME_DB_AURORA => Aurora::class,
        ServiceInterface::NAME_PHP => Php::class
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
     * @throws ServiceMismatchException when service isn't defined in service map
     */
    public function create(string $serviceName): ServiceInterface
    {
        if (!array_key_exists($serviceName, $this->serviceMap)) {
            throw new ServiceMismatchException(sprintf(
                'Service "%s" is not supported',
                $serviceName
            ));
        }

        return $this->container->create($this->serviceMap[$serviceName]);
    }
}
