<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Filesystem;

/**
 * Inteface for Restorable cloud directories
 */
interface FlagFileInterface
{
    public function exists();
    public function set();
    public function delete();
    public function getPath();
    public function getKey();
}
