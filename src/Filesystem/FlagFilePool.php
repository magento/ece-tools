<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Filesystem;

class FlagFilePool
{
    const REGENERATE_FLAG = 'regenerate';
    const SCD_IN_BUILD_FLAG = 'scd_in_build';
    /**
     * @var FlagFileInterface[]
     */
    private $flags;

    /**
     * @param FlagFileInterface[] $flags
     */
    public function __construct(array $flags)
    {
        $this->flags = $flags;
    }

    /**
     * Gets all flags, returns all if no filter is present, otherwise it filters down
     *
     * @param string[] $filter Match on provided filter
     * @return FlagFileInterface[]
     */
    public function get(array $filter = null)
    {
        if (!$filter) {
            return $this->flags;
        }

        return array_filter($this->flags, function ($flag) use ($filter) {
            if (in_array($flag->getKey(), $filter)) {
                return $flag;
            }
        });
    }

    /**
     * Get a single flag
     *
     * @param string $key Match on provided key
     * @return FlagFileInterface|null
     */
    public function getFlag(string $key)
    {
        $result = $this->get([$key]);
        if (!$result || !is_array($result)) {
            return null;
        }

        return array_pop($result);
    }
}
