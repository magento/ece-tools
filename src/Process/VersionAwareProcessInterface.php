<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Process;

/**
 * Version-dependent process interface.
 */
interface VersionAwareProcessInterface extends ProcessInterface
{
    /**
     * Checks whether process is available to be executed.
     *
     * @return bool
     */
    public function isAvailable(): bool;
}
