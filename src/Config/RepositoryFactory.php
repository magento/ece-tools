<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Config;

use Illuminate\Config\Repository;
use Illuminate\Contracts\Config\Repository as RepositoryInterface;
use Magento\MagentoCloud\App\ContainerInterface;

/**
 * Creates instances of config repository.
 */
class RepositoryFactory
{
    /**
     * Creates instances of Repository.
     *
     * @param array $items The config array
     * @return RepositoryInterface
     */
    public function create(array $items = []): RepositoryInterface
    {
        return new Repository($items);
    }
}
