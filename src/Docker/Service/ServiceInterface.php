<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Docker\Service;

/**
 * Service configuration container.
 */
interface ServiceInterface
{
    /**
     * Get service configuration.
     *
     * @return array
     */
    public function get(): array;
}
