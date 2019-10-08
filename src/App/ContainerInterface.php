<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

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
     * @param string $id
     * @param object $service
     * @return void
     */
    public function set(string $id, $service): void;
}
