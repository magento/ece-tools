<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Patch;

/**
 * Provides apply methods for patches.
 */
interface ApplierInterface
{

    /**
     * Applies patches
     *
     * @param array $paths Paths to patch
     * @return void
     */
    public function applyPatches(array $paths);

    /**
     * Unapply patches
     *
     * @return void
     */
    public function unapplyAllPatches();

}
