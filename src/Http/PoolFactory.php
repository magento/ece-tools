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
    /** @var ContainerInterface */
    private $container;

    /** @var ClientFactory */
    private $clientFactory;

    /** @var RequestFactory */
    private $requestFactory;

    /** @var UrlManager */
    private $urlManager;

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

    public function create(array $urls, array $config, array $clientConfig = [], string $requestMethod = 'GET'): Pool
    {
        $client = $this->clientFactory->create($clientConfig);

        return $this->container->create(Pool::class, [
            'client' => $client,
            'requests' => $this->yieldRequest($urls, $requestMethod),
            'config' => $config,
        ]);
    }

    public function yieldRequest(array $urls, string $requestMethod): \Iterator
    {
        foreach ($urls as $url) {
            $url = $this->urlManager->expandUrl($url);

            yield $this->requestFactory->create($requestMethod, $url);
        }
    }
}
