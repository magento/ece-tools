<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Filesystem\FlagFile;

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
     * Gets all flags, returns all if no filter is present, otherwise it filters down
     *
     * @param string[] $filter Match on provided filter
     * @return FlagInterface[]
     */
    public function get(array $filter = null)
    {
        if (!$filter) {
            return $this->flags;
        }

        return array_filter($this->flags, function (FlagInterface $flag) use ($filter) {
            return in_array($flag->getKey(), $filter);
        });
    }
}
