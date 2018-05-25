<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Http;

use GuzzleHttp\Psr7\Request;
use Magento\MagentoCloud\App\ContainerInterface;
use Psr\Http\Message\RequestInterface;

/**
 * Creates instances of Request.
 *
 * @see RequestInterface
 */
class RequestFactory
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
     * @param string $method
     * @param string $uri
     * @return RequestInterface
     */
    public function create(string $method, string $uri): RequestInterface
    {
        return $this->container->create(Request::class, ['method' => $method, 'uri' => $uri]);
    }
}
