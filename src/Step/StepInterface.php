<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Step;

/**
 * Step represent different application operations, such us:
 * - DI Compilation
 * - Static content deploy
 * - etc
 */
interface StepInterface
{
    /**
     * Executes the step.
     *
     * @return void
     * @throws StepException
     */
    public function execute();
}
