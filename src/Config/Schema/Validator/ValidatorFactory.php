<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Config\Schema\Validator;

use Magento\MagentoCloud\App\ContainerInterface;

/**
 * Creates instances of Validator.
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
     * @param string $class
     * @return ValidatorInterface
     */
    public function create(string $class): ValidatorInterface
    {
        return $this->container->create($class);
    }
}
