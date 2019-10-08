<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Config;

use Illuminate\Contracts\Config\Repository;

/**
 * Creates instances of config repository.
 */
class RepositoryFactory
{
    /**
     * Creates instances of Repository.
     *
     * @param array $items The config array
     * @return Repository
     */
    public function create(array $items = []): Repository
    {
        return new \Illuminate\Config\Repository($items);
    }
}
