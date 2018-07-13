<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Config;

/**
 * General config interface.
 */
interface ConfigInterface
{
    /**
     * Retrieve data by key.
     *
     * @param string $key
     * @param mixed $default
     * @return string|int|bool|array|null
     */
    public function get(string $key, $default = null);

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
     * Update data by key.
     *
     * @param string $key
     * @param mixed $value
     */
    public function set(string $key, $value);

    /**
     * Update current data.
     *
     * @param array $config
     */
    public function update(array $config);

    /**
     * Reset cached data.
     */
    public function reset();
}
