<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Process;

/**
 * Process represent different application operations, such us:
 * - DI Compilation
 * - Static content deploy
 * - etc
 */
interface ProcessInterface
{
    /**
     * Executes the process.
     *
     * @return void
     * @throws ProcessException
     */
    public function execute();
}
