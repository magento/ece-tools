<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Filesystem\Flag;

class Pool
{
    /**
     * @var FlagInterface[]
     */
    private $flags;

    /**
     * @param FlagInterface[] $flags
     */
    public function __construct(array $flags)
    {
        $this->flags = $flags;
    }

    /**
     * Gets flag by key, returns null if flag not exists.
     *
     * @param string $key
     * @return FlagInterface|null
     */
    public function get(string $key)
    {
        return $this->flags[$key] ?? null;
    }
}
