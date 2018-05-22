<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\App;

interface ContainerInterface extends \Psr\Container\ContainerInterface
{
    /**
     * @param string $abstract
     * @param array $params
     * @return mixed
     */
    public function create(string $abstract, array $params = []);
}
