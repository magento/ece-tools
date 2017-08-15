<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Container;

interface ContainerInterface
{
    /**
     * Retrieves object by abstract name.
     *
     * @param string $abstract
     * @param array $params
     * @return mixed
     */
    public function get(string $abstract, array $params = []);
}
