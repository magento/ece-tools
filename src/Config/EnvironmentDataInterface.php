<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Config;

/**
 * Interface for getting environment configuration
 *
 * @api
 */
interface EnvironmentDataInterface
{
    public const MOUNT_PUB_STATIC = 'pub/static';

    /**
     * 'getEnv' method is an abstraction for _ENV and getenv.
     * If _ENV is enabled in php.ini, use that.  If not, fall back to use getenv.
     * returns false if not found
     *
     * @param string $key
     * @return array|string|int|null|bool
     */
    public function getEnv(string $key);

    /**
     * Get routes information from MagentoCloud environment variable.
     *
     * @return array
     */
    public function getRoutes(): array;

    /**
     * Get relationships information from MagentoCloud environment variable.
     *
     * @return array
     */
    public function getRelationships(): array;

    /**
     * Get custom variables from MagentoCloud environment variable.
     *
     * @return array
     */
    public function getVariables(): array;

    /**
     * @return array
     */
    public function getApplication(): array;

    /**
     * Returns name of environment branch
     *
     * @return string
     */
    public function getBranchName(): string;

    /**
     * Returns MAGE_MODE environment variable
     *
     * @return string|null
     */
    public function getMageMode(): ?string;

    /**
     * Checks whether application has specific mount.
     *
     * @param string $name
     * @return bool
     */
    public function hasMount(string $name): bool;
}
