<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud;

use Illuminate\Container\Container;
use Magento\MagentoCloud\Command\Build;
use Magento\MagentoCloud\Command\Deploy;
use Magento\MagentoCloud\Process\ProcessInterface;
use Magento\MagentoCloud\Process\ProcessPool;
use Magento\MagentoCloud\Process\Build as BuildProcess;
use Magento\MagentoCloud\Process\Deploy as DeployProcess;

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
     * @param Container $container
     * @return Container
     */
    private function configureContainer(Container $container)
    {
        /**
         * Interface to implementation binding.
         */
        $container->singleton(
            \Psr\Log\LoggerInterface::class,
            \Magento\MagentoCloud\Logger\Logger::class
        );
        $container->singleton(
            \Magento\MagentoCloud\Shell\ShellInterface::class,
            \Magento\MagentoCloud\Shell\Shell::class
        );
        $container->singleton(\Magento\MagentoCloud\Environment::class);
        $container->singleton(\Magento\MagentoCloud\DB\Adapter::class);

        /**
         * Contextual binding.
         */
        $container->when(Build::class)
            ->needs(ProcessInterface::class)
            ->give(function () use ($container) {
                return $container->makeWith(ProcessPool::class, [
                    'processes' => [
                        100 => $container->make(BuildProcess\PreBuild::class),
                        200 => $container->make(BuildProcess\ApplyPatches::class),
                        300 => $container->make(BuildProcess\MarshallingFiles::class),
                        400 => $container->make(BuildProcess\CopySampleData::class),
                        500 => $container->make(BuildProcess\CompileDi::class),
                        600 => $container->make(BuildProcess\ComposerDumpAutoload::class),
                        700 => $container->make(BuildProcess\DeployStaticContent::class),
                        800 => $container->make(BuildProcess\ClearInitDirectory::class),
                        900 => $container->make(BuildProcess\BackupToInitDirectory::class),
                    ],
                ]);
            });
        $container->when(Deploy::class)
            ->needs(ProcessInterface::class)
            ->give(function () use ($container) {
                return $container->makeWith(ProcessPool::class, [
                    'processes' => [
                        100 => $container->make(DeployProcess\PreDeploy::class),
                        200 => $container->make(DeployProcess\ConfigFileCreator::class),
                        300 => $container->make(DeployProcess\MagentoMode::class),
                        400 => $container->make(DeployProcess\InstallUpdate::class),

                        600 => $container->make(DeployProcess\DisableGoogleAnalytics::class),
                    ],
                ]);
            });
        $container->when(\Magento\MagentoCloud\Config\Build::class)
            ->needs(\Magento\MagentoCloud\Filesystem\Reader\ReaderInterface::class)
            ->give(\Magento\MagentoCloud\Config\Build\Reader::class);

        return $container;
    }
}
