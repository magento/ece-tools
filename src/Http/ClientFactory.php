<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Http;

use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use Magento\MagentoCloud\App\ContainerInterface;

/**
 * Creates configured instances of Client.
 *
 * @see Client
 */
class ClientFactory
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
     * Creates a Client instance.
     *
     * @param array $config
     * @return Client|ClientInterface
     */
    public function create(array $config = []): ClientInterface
    {
        return $this->container->create(Client::class, ['config' => $config]);
    }
}
