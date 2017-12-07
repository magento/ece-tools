<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Filesystem;

/**
 * Inteface for FlagFiles
 */
interface FlagFileInterface
{
    /**
     * Determines whether or not a flag exists
     * @return bool
     */
    public function exists(): bool;

    /**
     * Sets a flag on the file system
     * @return bool
     */
    public function set(): bool;

    /**
     * Deletes a flag from the filesystem
     * @return bool
     */
    public function delete(): bool;

    /**
     * Return our path
     *
     * @return string
     */
    public function getPath(): string;

    /**
     * Return our key
     *
     * @return string
     */
    public function getKey();
}
