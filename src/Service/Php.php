<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Service;

/**
 * Returns PHP version
 */
class Php implements ServiceInterface
{
    /**
     * @inheritDoc
     */
    public function getConfiguration(): array
    {
        return [];
    }

    /**
     * @inheritDoc
     */
    public function getVersion(): string
    {
        return explode('-', PHP_VERSION)[0];
    }
}
