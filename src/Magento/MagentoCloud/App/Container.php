<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\App;

use Magento\MagentoCloud\Command\Build;
use Magento\MagentoCloud\Command\Deploy;
use Magento\MagentoCloud\Command\ConfigDump;
use Magento\MagentoCloud\Process\ProcessInterface;
use Magento\MagentoCloud\Process\ProcessPool;
use Magento\MagentoCloud\Process\Build as BuildProcess;
use Magento\MagentoCloud\Process\Deploy as DeployProcess;
use Magento\MagentoCloud\Process\ConfigDump as ConfigDumpProcess;
use Monolog\Formatter\LineFormatter;
use Monolog\Handler\StreamHandler;
use Psr\Container\ContainerInterface;

/**
 * @inheritdoc
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class Container extends \Illuminate\Container\Container implements ContainerInterface
{
    /**
     * @param string $root
     * @param array $config
     */
    public function __construct(string $root, array $config)
    {
        $this->singleton(\Magento\MagentoCloud\Filesystem\DirectoryList::class, function () use ($root, $config) {
            return new \Magento\MagentoCloud\Filesystem\DirectoryList($root, $config);
        });
        /**
         * Interface to implementation binding.
         */
        $this->singleton(
            \Magento\MagentoCloud\Shell\ShellInterface::class,
            \Magento\MagentoCloud\Shell\Shell::class
        );
        $this->singleton(\Magento\MagentoCloud\Config\Environment::class);
        $this->singleton(\Magento\MagentoCloud\DB\Adapter::class);
        $this->singleton(\Magento\MagentoCloud\Config\Build::class);
        $this->singleton(\Magento\MagentoCloud\Config\Deploy::class);
        $this->singleton(\Psr\Log\LoggerInterface::class, $this->createLogger('default'));
        $this->singleton(\Magento\MagentoCloud\Util\ComponentInfo::class);
        $this->singleton(\Composer\Composer::class, function () {
            $directoryList = $this->get(\Magento\MagentoCloud\Filesystem\DirectoryList::class);

            return \Composer\Factory::create(
                new \Composer\IO\BufferIO(),
                $directoryList->getMagentoRoot() . DIRECTORY_SEPARATOR . 'composer.json'
            );
        });

        /**
         * Contextual binding.
         */
        $this->when(Build::class)
            ->needs(ProcessInterface::class)
            ->give(function () {
                return $this->makeWith(ProcessPool::class, [
                    'processes' => [
                        $this->make(BuildProcess\PreBuild::class),
                        $this->make(BuildProcess\ApplyPatches::class),
                        $this->make(BuildProcess\MarshallFiles::class),
                        $this->make(BuildProcess\CopySampleData::class),
                        $this->make(BuildProcess\CompileDi::class),
                        $this->make(BuildProcess\ComposerDumpAutoload::class),
                        $this->make(BuildProcess\DeployStaticContent::class),
                        $this->make(BuildProcess\ClearInitDirectory::class),
                        $this->make(BuildProcess\BackupToInitDirectory::class),
                    ],
                ]);
            });
        $this->when(Deploy::class)
            ->needs(ProcessInterface::class)
            ->give(function () {
                return $this->makeWith(ProcessPool::class, [
                    'processes' => [
                        $this->make(DeployProcess\PreDeploy::class),
                        $this->make(DeployProcess\CreateConfigFile::class),
                        $this->make(DeployProcess\SetMode::class),
                        $this->make(DeployProcess\InstallUpdate::class),
                        $this->make(DeployProcess\DeployStaticContent::class),
                        $this->make(DeployProcess\DisableGoogleAnalytics::class),
                    ],
                ]);
            });
        $this->when(ConfigDump::class)
            ->needs(ProcessInterface::class)
            ->give(function () {
                return $this->makeWith(ProcessPool::class, [
                    'processes' => [
                        $this->make(ConfigDumpProcess\Import::class),
                    ],
                ]);
            });
        $this->when(DeployProcess\PreDeploy::class)
            ->needs(ProcessInterface::class)
            ->give(function () {
                return $this->makeWith(ProcessPool::class, [
                    'processes' => [
                        $this->make(DeployProcess\PreDeploy\RestoreWritableDirectories::class),
                        $this->make(DeployProcess\PreDeploy\CleanRedisCache::class),
                        $this->make(DeployProcess\PreDeploy\CleanFileCache::class),
                        $this->make(DeployProcess\PreDeploy\ProcessStaticContent::class),
                    ],
                ]);
            });
        $this->when(DeployProcess\DeployStaticContent::class)
            ->needs(ProcessInterface::class)
            ->give(function () {
                return $this->makeWith(ProcessPool::class, [
                    'processes' => [
                        $this->get(DeployProcess\DeployStaticContent\GenerateFresh::class),
                    ],
                ]);
            });
        $this->when(\Magento\MagentoCloud\Config\Build::class)
            ->needs(\Magento\MagentoCloud\Filesystem\Reader\ReaderInterface::class)
            ->give(\Magento\MagentoCloud\Config\Build\Reader::class);
    }

    /**
     * @inheritdoc
     */
    public function get($id)
    {
        return $this->resolve($id);
    }

    /**
     * @param string $name
     * @return \Closure
     */
    private function createLogger(string $name): \Closure
    {
        return function () use ($name) {
            $formatter = new LineFormatter("[%datetime%] %level_name%: %message% %context% %extra%\n");
            $formatter->allowInlineLineBreaks();
            $formatter->ignoreEmptyContextAndExtra();

            $magentoRoot = $this->get(\Magento\MagentoCloud\Filesystem\DirectoryList::class)
                ->getMagentoRoot();

            return $this->makeWith(\Monolog\Logger::class, [
                'name' => $name,
                'handlers' => [
                    (new StreamHandler($magentoRoot . '/var/log/cloud.log'))
                        ->setFormatter($formatter),
                    (new StreamHandler('php://stdout'))
                        ->setFormatter($formatter),
                ],
            ]);
        };
    }
}
