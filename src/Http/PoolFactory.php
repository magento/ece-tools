<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Http;

use GuzzleHttp\Pool;
use Magento\MagentoCloud\App\ContainerInterface;
use Magento\MagentoCloud\Util\UrlManager;
use Psr\Http\Message\RequestInterface;

/**
 * Create Guzzle request pools.
 */
class PoolFactory
{
    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * @var ClientFactory
     */
    private $clientFactory;

    /**
     * @var RequestFactory
     */
    private $requestFactory;

    /**
     * @var UrlManager
     */
    private $urlManager;

    /**
     * @param ContainerInterface $container
     * @param ClientFactory $clientFactory
     * @param RequestFactory $requestFactory
     * @param UrlManager $urlManager
     */
    public function __construct(
        ContainerInterface $container,
        ClientFactory $clientFactory,
        RequestFactory $requestFactory,
        UrlManager $urlManager
    ) {
        $this->container = $container;
        $this->clientFactory = $clientFactory;
        $this->requestFactory = $requestFactory;
        $this->urlManager = $urlManager;
    }

    /**
     * Create a Pool instance.
     *
     * @param array $urls
     * @param array $config Configuration options for Pool instance
     * @param array $clientConfig
     * @param string $requestMethod
     * @return Pool
     */
    public function create(array $urls, array $config, array $clientConfig = [], string $requestMethod = 'GET'): Pool
    {
        $client = $this->clientFactory->create($clientConfig);

        $requests = array_map(function ($url) use ($requestMethod) {
            return $this->requestFactory->create($requestMethod, $this->urlManager->expandUrl($url));
        }, $urls);

        return $this->container->create(Pool::class, compact('client', 'requests', 'config'));
    }
}
