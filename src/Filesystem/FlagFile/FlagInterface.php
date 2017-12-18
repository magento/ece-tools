<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Filesystem\FlagFile;

/**
 * Interface for FlagFiles.
 */
interface FlagInterface
{
    /**
     * Return flag path.
     *
     * @return string
     */
    public function getPath(): string;

    /**
     * Return flag key.
     *
     * @return string
     */
    public function getKey(): string;
}
