<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Container;

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
    public function __construct()
    {
        /**
         * Interface to implementation binding.
         */
        $this->singleton(
            \Psr\Log\LoggerInterface::class,
            $this->createLogger('default')
        );
        $this->singleton(
            \Magento\MagentoCloud\Shell\ShellInterface::class,
            \Magento\MagentoCloud\Shell\Shell::class
        );
        $this->singleton(\Magento\MagentoCloud\Config\Environment::class);
        $this->singleton(\Magento\MagentoCloud\DB\Adapter::class);
        $this->singleton(\Magento\MagentoCloud\Config\Build::class);
        $this->singleton(\Magento\MagentoCloud\Config\Deploy::class);

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
     * @inheritdoc
     */
    public function has($id)
    {
        return $this->bound($id);
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

            return $this->makeWith(\Monolog\Logger::class, [
                'name' => $name,
                'handlers' => [
                    (new StreamHandler(MAGENTO_ROOT . 'var/log/cloud_build.log'))
                        ->setFormatter($formatter),
                    (new StreamHandler('php://stdout'))
                        ->setFormatter($formatter),
                ],
            ]);
        };
    }
}
