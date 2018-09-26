<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Process;

use Magento\MagentoCloud\App\GenericException;

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
     * @throws GenericException
     */
    public function execute();
}
