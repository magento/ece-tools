<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\App;

/**
 * General interface for DI Container.
 */
interface ContainerInterface extends \Psr\Container\ContainerInterface
{
    /**
     * Resolve the given type from the container.
     *
     * @param string $abstract
     * @param array $params
     * @return mixed
     */
    public function create(string $abstract, array $params = []);

    /**
     * Register a binding with the container.
     *
     * @param string $abstract
     * @param string|\Closure $concrete
     * @param bool $shared
     * @return void
     */
    public function set(string $abstract, $concrete, bool $shared = true);
}
