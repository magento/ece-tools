<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud;

use Illuminate\Container\Container;
use Illuminate\Contracts\Container\Container as ContainerContract;
use Magento\MagentoCloud\Command\Build;
use Magento\MagentoCloud\Command\Deploy;
use Magento\MagentoCloud\Process\ProcessInterface;
use Magento\MagentoCloud\Process\ProcessPool;

/**
 * @inheritdoc
 */
class Application extends \Symfony\Component\Console\Application
{
    /**
     * @inheritdoc
     */
    public function __construct()
    {
        $container = $this->configureContainer(
            Container::getInstance()
        );

        Container::setInstance($container);

        parent::__construct();
    }

    /**
     * @inheritdoc
     */
    protected function getDefaultCommands()
    {
        return array_merge(
            parent::getDefaultCommands(),
            [
                Container::getInstance()->make(Build::class),
                Container::getInstance()->make(Deploy::class),
            ]
        );
    }

    /**
     * Binds interfaces and contexts.
     *
     * @param ContainerContract $container
     * @return ContainerContract
     */
    private function configureContainer(ContainerContract $container)
    {
        /**
         * Interface to implementation binding.
         */
        $container->bind(
            \Psr\Log\LoggerInterface::class,
            \Magento\MagentoCloud\Logger\Logger::class
        );
        $container->bind(
            \Magento\MagentoCloud\Shell\ShellInterface::class,
            \Magento\MagentoCloud\Shell\Shell::class
        );

        /**
         * Contextual binding.
         */
        $container->when(Build::class)
            ->needs(ProcessInterface::class)
            ->give(function () use ($container) {
                return $container->makeWith(ProcessPool::class, [
                    'processes' => [
                        100 => $container->make(\Magento\MagentoCloud\Process\ApplyPatches::class),
                        300 => $container->make(\Magento\MagentoCloud\Process\CompileDi::class),
                        400 => $container->make(\Magento\MagentoCloud\Process\ComposerDumpAutoload::class),
                    ],
                ]);
            });
        $container->when(Deploy::class)
            ->needs(ProcessInterface::class)
            ->give(function () use ($container) {
                return $container->makeWith(ProcessPool::class, [
                    'processes' => [
                    ],
                ]);
            });
        $container->when(\Magento\MagentoCloud\Config\Build::class)
            ->needs(\Magento\MagentoCloud\Filesystem\Reader\ReaderInterface::class)
            ->give(function () use ($container) {
                return $container->make(\Magento\MagentoCloud\Config\Build\Reader::class);
            });

        return $container;
    }
}
