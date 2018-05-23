<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Config;

use Magento\MagentoCloud\App\Container;
use Magento\MagentoCloud\App\ContainerInterface;

/**
 * Created instances of specific validators.
 */
class ValidatorFactory
{
    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * @param string $validator
     * @return ValidatorInterface
     */
    public function create(string $validator): ValidatorInterface
    {
        return $this->container->create($validator);
    }
}
