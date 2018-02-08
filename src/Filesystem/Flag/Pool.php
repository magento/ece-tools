<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Filesystem\Flag;

/**
 * The pool of available flags.
 */
class Pool
{
    /**
     * @var array
     */
    private $flags;

    /**
     * @param array $flags
     */
    public function __construct(array $flags)
    {
        $this->flags = $flags;
    }

    /**
     * Gets flag path by key, returns null if flag not exists.
     *
     * @param string $key
     * @return string|null
     */
    public function get(string $key)
    {
        return $this->flags[$key] ?? null;
    }
}
