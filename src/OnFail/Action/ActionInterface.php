<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\OnFail\Action;

/**
 * Action represent small scenario action on fail such us create some flags, etc
 */
interface ActionInterface
{
    /**
     * Executes the action.
     *
     * @return void
     * @throws ActionException
     */
    public function execute(): void;
}
