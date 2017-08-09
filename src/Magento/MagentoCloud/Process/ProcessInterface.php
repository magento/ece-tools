<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Process;

interface ProcessInterface
{
    /**
     * Executes the process.
     *
     * @return void
     */
    public function execute();
}
