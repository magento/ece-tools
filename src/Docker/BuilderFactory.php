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
    const BUILDER_PROD = 'prod';
    const BUILDER_DEV = 'dev';
    const BUILDER_TEST_V1 = 'test-v1';
    const BUILDER_TEST_V2 = 'test-v2';

    /**
     * @var array
     */
    private static $map = [
        self::BUILDER_DEV => DevelopBuilder::class,
        self::BUILDER_PROD => ProductionBuilder::class,
        /** Internal CI configurations. */
        self::BUILDER_TEST_V1 => IntegrationV1Builder::class,
        self::BUILDER_TEST_V2 => IntegrationV2Builder::class
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
