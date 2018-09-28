<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Config;

use Magento\MagentoCloud\Filesystem\FileSystemException;

/**
 * General config interface.
 */
interface ConfigInterface
{
    /**
     * Retrieve data by key.
     *
     * @param string $key
     * @return string|int|bool|array|null
     */
    public function get(string $key);

    /**
     * Assert data by key.
     *
     * @param string $key
     * @return bool
     */
    public function has(string $key): bool;

    /**
     * Retrieve all data.
     *
     * @return array
     */
    public function all(): array;

    /**
     * Update current data.
     *
     * @param array $config
     * @throws FileSystemException
     */
    public function update(array $config);

    /**
     * Reset cached data.
     */
    public function reset();
}
