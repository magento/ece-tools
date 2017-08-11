<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud;

use Illuminate\Container\Container;
use Magento\MagentoCloud\Command\Build;
use Magento\MagentoCloud\Command\Deploy;
use Magento\MagentoCloud\Command\SCDConfigDump;
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
        Container::setInstance(
            $this->createContainer()
        );

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
                Container::getInstance()->make(SCDConfigDump::class),
            ]
        );
    }

    /**
     * Binds interfaces and contexts.
     *
     * @return Container
     */
    private function createContainer()
    {
        $container = Container::getInstance();

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
        $container->singleton(\Magento\MagentoCloud\Config\Environment::class);
        $container->singleton(\Magento\MagentoCloud\DB\Adapter::class);
        $container->singleton(\Magento\MagentoCloud\Config\Build::class);
        $container->singleton(\Magento\MagentoCloud\Config\Deploy::class);

        /**
         * Contextual binding.
         */
        $container->when(Build::class)
            ->needs(ProcessInterface::class)
            ->give(function () use ($container) {
                return $container->makeWith(ProcessPool::class, [
                    'processes' => [
                        $container->make(BuildProcess\PreBuild::class),
                        $container->make(BuildProcess\ApplyPatches::class),
                        $container->make(BuildProcess\MarshallFiles::class),
                        $container->make(BuildProcess\CopySampleData::class),
                        $container->make(BuildProcess\CompileDi::class),
                        $container->make(BuildProcess\ComposerDumpAutoload::class),
                        $container->make(BuildProcess\DeployStaticContent::class),
                        $container->make(BuildProcess\ClearInitDirectory::class),
                        $container->make(BuildProcess\BackupToInitDirectory::class),
                    ],
                ]);
            });
        $container->when(Deploy::class)
            ->needs(ProcessInterface::class)
            ->give(function () use ($container) {
                return $container->makeWith(ProcessPool::class, [
                    'processes' => [
                        $container->make(DeployProcess\PreDeploy::class),
                        $container->make(DeployProcess\CreateConfigFile::class),
                        $container->make(DeployProcess\SetMode::class),
                        $container->make(DeployProcess\InstallUpdate::class),
                        $container->make(DeployProcess\DeployStaticContent::class),
                        $container->make(DeployProcess\DisableGoogleAnalytics::class),
                    ],
                ]);
            });
        $container->when(SCDConfigDump::class)
            ->needs(ProcessInterface::class)
            ->give(function () use ($container) {
                return $container->makeWith(ProcessPool::class, [
                    'processes' => [
                    ],
                ]);
            });
        $container->when(\Magento\MagentoCloud\Config\Build::class)
            ->needs(\Magento\MagentoCloud\Filesystem\Reader\ReaderInterface::class)
            ->give(\Magento\MagentoCloud\Config\Build\Reader::class);

        return $container;
    }
}
