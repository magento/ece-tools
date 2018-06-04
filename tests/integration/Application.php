<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Integration;

use Magento\MagentoCloud\App\ContainerInterface;
use Magento\MagentoCloud\App\Logger;
use Psr\Log\LoggerInterface;

/**
 * @inheritdoc
 */
class Application extends \Magento\MagentoCloud\Application
{
    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * @inheritdoc
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;

        parent::__construct($container);
    }

    /**
     * @inheritdoc
     */
    public function get($name)
    {
        $this->container->set(LoggerInterface::class, Logger::class);

        return parent::get($name);
    }
}
