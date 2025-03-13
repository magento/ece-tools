<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Config\Schema\Validator;

use Magento\MagentoCloud\App\ContainerInterface;
use Magento\MagentoCloud\Config\Validator\ResultFactory;

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
     * @param array $arguments
     * @return ValidatorInterface
     */
    public function create(string $class, array $arguments = []): ValidatorInterface
    {
        if (count($arguments)) {
            array_unshift($arguments, $this->container->create(ResultFactory::class));
        }

        return $this->container->create($class, $arguments);
    }
}
