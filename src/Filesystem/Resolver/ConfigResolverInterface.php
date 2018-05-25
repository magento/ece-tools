<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Filesystem\Resolver;

/**
 * Resolver for determines correct path of config.
 */
interface ConfigResolverInterface
{
    /**
     * Determines correct path of config.
     *
     * @return string
     */
    public function resolve(): string;
}
