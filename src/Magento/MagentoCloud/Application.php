<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud;

use Composer\Composer;
use Magento\MagentoCloud\Command\Build;
use Magento\MagentoCloud\Command\Deploy;
use Magento\MagentoCloud\Command\ConfigDump;
use Psr\Container\ContainerInterface;

/**
 * @inheritdoc
 */
class Application extends \Symfony\Component\Console\Application
{
    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * @var Composer
     */
    private $composer;

    /**
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
        $this->composer = $container->get(Composer::class);

        parent::__construct(
            $this->composer->getPackage()->getPrettyName(),
            $this->composer->getPackage()->getPrettyVersion()
        );
    }

    /**
     * @inheritdoc
     */
    protected function getDefaultCommands()
    {
        return array_merge(
            parent::getDefaultCommands(),
            [
                $this->container->get(Build::class),
                $this->container->get(Deploy::class),
                $this->container->get(ConfigDump::class),
            ]
        );
    }

    /**
     * @return ContainerInterface
     */
    public function getContainer()
    {
        return $this->container;
    }

    public function getComposer()
    {
        $this->composer;
    }
}
