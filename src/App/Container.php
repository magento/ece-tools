<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\App;

use Magento\MagentoCloud\Command\Build;
use Magento\MagentoCloud\Command\PreStart;
use Magento\MagentoCloud\Command\Deploy;
use Magento\MagentoCloud\Command\ConfigDump;
use Magento\MagentoCloud\Process\ProcessInterface;
use Magento\MagentoCloud\Process\ProcessPool;
use Magento\MagentoCloud\Process\Build as BuildProcess;
use Magento\MagentoCloud\Process\PreStart as PreStartProcess;
use Magento\MagentoCloud\Process\Deploy as DeployProcess;
use Magento\MagentoCloud\Process\ConfigDump as ConfigDumpProcess;
use Monolog\Formatter\LineFormatter;
use Monolog\Handler\StreamHandler;
use Psr\Container\ContainerInterface;

/**
 * @inheritdoc
 * @codeCoverageIgnore
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class Container extends \Illuminate\Container\Container implements ContainerInterface
{
    /**
     * @param string $root
     * @param array $config
     *
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function __construct(string $root, array $config)
    {
        /**
         * Instance configuration.
         */
        $this->singleton(\Magento\MagentoCloud\Filesystem\DirectoryList::class, function () use ($root, $config) {
            return new \Magento\MagentoCloud\Filesystem\DirectoryList($root, $config);
        });
        $this->singleton(\Composer\Composer::class, function () {
            $directoryList = $this->get(\Magento\MagentoCloud\Filesystem\DirectoryList::class);

            return \Composer\Factory::create(
                new \Composer\IO\BufferIO(),
                $directoryList->getMagentoRoot() . '/composer.json'
            );
        });
        /**
         * Interface to implementation binding.
         */
        $this->singleton(
            \Magento\MagentoCloud\Shell\ShellInterface::class,
            \Magento\MagentoCloud\Shell\Shell::class
        );
        $this->singleton(\Magento\MagentoCloud\Config\Environment::class);
        $this->singleton(\Magento\MagentoCloud\Config\Build::class);
        $this->singleton(\Magento\MagentoCloud\Config\Deploy::class);
        $this->singleton(\Psr\Log\LoggerInterface::class, $this->createLogger('default'));
        $this->singleton(\Magento\MagentoCloud\Package\Manager::class);
        $this->singleton(\Magento\MagentoCloud\Package\MagentoVersion::class);
        $this->singleton(\Magento\MagentoCloud\Util\UrlManager::class);
        $this->singleton(
            \Magento\MagentoCloud\DB\ConnectionInterface::class,
            \Magento\MagentoCloud\DB\Connection::class
        );
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
                        $this->make(BuildProcess\ClearBackupDirectory::class),
                        $this->make(BuildProcess\CopyToBackupDirectory::class),
                    ],
                ]);
            });

        $this->when(PreStart::class)
            ->needs(ProcessInterface::class)
            ->give(function () {
                return $this->makeWith(ProcessPool::class, [
                    'processes' => [
                        $this->make(PreStartProcess\RestoreFromBuild::class)
                    ],
                ]);
            });

        $this->when(Deploy::class)
            ->needs(ProcessInterface::class)
            ->give(function () {
                return $this->makeWith(ProcessPool::class, [
                    'processes' => [
                        $this->make(DeployProcess\VerifyPreStart::class),
                        $this->make(DeployProcess\ClearEnvironmentCaches::class),
                        $this->make(DeployProcess\CreateConfigFile::class),
                        $this->make(DeployProcess\SetMode::class),
                        $this->make(DeployProcess\InstallUpdate::class),
                        $this->make(DeployProcess\DeployStaticContent::class),
                        $this->make(DeployProcess\DisableGoogleAnalytics::class),
                    ],
                ]);
            });
        $this->when(DeployProcess\InstallUpdate\Install::class)
            ->needs(ProcessInterface::class)
            ->give(function () {
                return $this->makeWith(ProcessPool::class, [
                    'processes' => [
                        $this->make(DeployProcess\InstallUpdate\Install\EmailChecker::class),
                        $this->make(DeployProcess\InstallUpdate\Install\Setup::class),
                        $this->make(DeployProcess\InstallUpdate\Install\SecureAdmin::class),
                        $this->make(DeployProcess\InstallUpdate\ConfigUpdate::class),
                        $this->make(DeployProcess\InstallUpdate\Install\ResetPassword::class),
                    ],
                ]);
            });
        $this->when(DeployProcess\InstallUpdate\Update::class)
            ->needs(ProcessInterface::class)
            ->give(function () {
                return $this->makeWith(ProcessPool::class, [
                    'processes' => [
                        $this->make(DeployProcess\InstallUpdate\ConfigUpdate::class),
                        $this->make(DeployProcess\InstallUpdate\Update\SetAdminUrl::class),
                        $this->make(DeployProcess\InstallUpdate\Update\Setup::class),
                        $this->make(DeployProcess\InstallUpdate\Update\AdminCredentials::class),
                        $this->make(DeployProcess\InstallUpdate\Update\ClearMagentoCache::class),
                    ],
                ]);
            });
        $this->when(DeployProcess\InstallUpdate\ConfigUpdate::class)
            ->needs(ProcessInterface::class)
            ->give(function () {
                return $this->makeWith(ProcessPool::class, [
                    'processes' => [
                        $this->make(DeployProcess\InstallUpdate\ConfigUpdate\DbConnection::class),
                        $this->make(DeployProcess\InstallUpdate\ConfigUpdate\Amqp::class),
                        $this->make(DeployProcess\InstallUpdate\ConfigUpdate\Redis::class),
                        $this->make(DeployProcess\InstallUpdate\ConfigUpdate\SearchEngine::class),
                        $this->make(DeployProcess\InstallUpdate\ConfigUpdate\Urls::class),
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
        $this->when(DeployProcess\DeployStaticContent::class)
            ->needs(ProcessInterface::class)
            ->give(function () {
                return $this->makeWith(ProcessPool::class, [
                    'processes' => [
                        $this->get(DeployProcess\DeployStaticContent\Generate::class),
                    ],
                ]);
            });
        $this->when(\Magento\MagentoCloud\Config\Build::class)
            ->needs(\Magento\MagentoCloud\Filesystem\Reader\ReaderInterface::class)
            ->give(\Magento\MagentoCloud\Config\Build\Reader::class);
        $this->when(BuildProcess\DeployStaticContent::class)
            ->needs(ProcessInterface::class)
            ->give(function () {
                return $this->makeWith(ProcessPool::class, [
                    'processes' => [
                        $this->get(BuildProcess\DeployStaticContent\Generate::class),
                    ],
                ]);
            });
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
