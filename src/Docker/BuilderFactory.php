<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Docker;

use Magento\MagentoCloud\App\ContainerInterface;

/**
 * Factory class for Docker builder.
 */
class BuilderFactory
{
    const BUILDER_DEV = 'dev';
    const BUILDER_TEST = 'test';

    /**
     * @var array
     */
    private static $map = [
        self::BUILDER_DEV => DevBuilder::class,
        self::BUILDER_TEST => IntegrationBuilder::class,
    ];

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
     * @param string $strategy
     * @return BuilderInterface
     */
    public function create(string $strategy): BuilderInterface
    {
        return $this->container->create(
            self::$map[$strategy]
        );
    }
}
