<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Config;

use Illuminate\Contracts\Config\Repository;
use Magento\MagentoCloud\App\ContainerInterface;

/**
 * Creates instances of config repository.
 */
class RepositoryFactory
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
     * Creates instances of Repository.
     *
     * @param array $items The config array
     * @return Repository
     */
    public function create(array $items = []): Repository
    {
        return $this->container->create(\Illuminate\Config\Repository::class, ['items' => $items]);
    }
}
