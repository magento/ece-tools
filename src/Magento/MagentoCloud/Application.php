<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud;

use Illuminate\Container\Container;
use Magento\MagentoCloud\Command\Build;
use Magento\MagentoCloud\Command\Deploy;
use Magento\MagentoCloud\Command\ConfigDump;
use Magento\MagentoCloud\Process\ProcessInterface;
use Magento\MagentoCloud\Process\ProcessPool;
use Magento\MagentoCloud\Process\Build as BuildProcess;
use Magento\MagentoCloud\Process\Deploy as DeployProcess;
use Magento\MagentoCloud\Process\ConfigDump as ConfigDumpProcess;

/**
 * @inheritdoc
 */
class Application extends \Symfony\Component\Console\Application
{
    /**
     * @var Container
     */
    private $container;

    /**
     * @inheritdoc
     */
    public function __construct()
    {
        $this->container = $this->createContainer();

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
                $this->container->make(Build::class),
                $this->container->make(Deploy::class),
                $this->container->make(ConfigDump::class),
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
            function () use ($container) {
                $formatter = new \Monolog\Formatter\LineFormatter();
                $formatter->allowInlineLineBreaks(true);

                return $container->makeWith(\Monolog\Logger::class, [
                    'name' => 'default',
                    'handlers' => [
                        (new \Monolog\Handler\StreamHandler(MAGENTO_ROOT . 'var/log/cloud_build.log'))
                            ->setFormatter($formatter),
                        (new \Monolog\Handler\StreamHandler('php://stdout'))
                            ->setFormatter($formatter),
                    ],
                ]);
            }
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
        $container->when(ConfigDump::class)
            ->needs(ProcessInterface::class)
            ->give(function () use ($container) {
                return $container->makeWith(ProcessPool::class, [
                    'processes' => [
                        $container->make(ConfigDumpProcess\Import::class),
                    ],
                ]);
            });
        $container->when(\Magento\MagentoCloud\Config\Build::class)
            ->needs(\Magento\MagentoCloud\Filesystem\Reader\ReaderInterface::class)
            ->give(\Magento\MagentoCloud\Config\Build\Reader::class);

        return Container::setInstance($container);
    }
}
