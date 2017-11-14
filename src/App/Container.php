<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\App;

use Magento\MagentoCloud\Command\Build;
use Magento\MagentoCloud\Command\DbDump;
use Magento\MagentoCloud\Command\Deploy;
use Magento\MagentoCloud\Command\ConfigDump;
use Magento\MagentoCloud\Command\PostDeploy;
use Magento\MagentoCloud\Config\ValidatorInterface;
use Magento\MagentoCloud\Config\Validator as ConfigValidator;
use Magento\MagentoCloud\DB\Data\ConnectionInterface;
use Magento\MagentoCloud\DB\Data\ReadConnection;
use Magento\MagentoCloud\Process\ProcessInterface;
use Magento\MagentoCloud\Process\ProcessComposite;
use Magento\MagentoCloud\Process\Build as BuildProcess;
use Magento\MagentoCloud\Process\DbDump as DbDumpProcess;
use Magento\MagentoCloud\Process\Deploy as DeployProcess;
use Magento\MagentoCloud\Process\ConfigDump as ConfigDumpProcess;
use Magento\MagentoCloud\Process\PostDeploy as PostDeployProcess;
use Psr\Container\ContainerInterface;

/**
 * @inheritdoc
 * @codeCoverageIgnore
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class Container implements ContainerInterface
{
    /**
     * @var \Illuminate\Container\Container
     */
    private $container;

    /**
     * @param string $root
     * @param array $config
     *
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function __construct(string $root, array $config)
    {
        /**
         * Creating concrete container.
         */
        $this->container = new \Illuminate\Container\Container();

        /**
         * Instance configuration.
         */
        $this->container->singleton(
            \Magento\MagentoCloud\Filesystem\DirectoryList::class,
            function () use ($root, $config) {
                return new \Magento\MagentoCloud\Filesystem\DirectoryList($root, $config);
            }
        );
        $this->container->singleton(\Magento\MagentoCloud\Filesystem\FileList::class);
        $this->container->singleton(\Composer\Composer::class, function () {
            $fileList = $this->get(\Magento\MagentoCloud\Filesystem\FileList::class);

            return \Composer\Factory::create(
                new \Composer\IO\BufferIO(),
                $fileList->getComposer()
            );
        });
        /**
         * Interface to implementation binding.
         */
        $this->container->singleton(
            \Magento\MagentoCloud\Shell\ShellInterface::class,
            \Magento\MagentoCloud\Shell\Shell::class
        );
        $this->container->singleton(\Magento\MagentoCloud\Config\Environment::class);
        $this->container->singleton(\Magento\MagentoCloud\Config\Build::class);
        $this->container->singleton(\Magento\MagentoCloud\Config\Deploy::class);
        $this->container->singleton(\Psr\Log\LoggerInterface::class, function () {
            return new \Monolog\Logger(
                'default',
                $this->container->make(\Magento\MagentoCloud\App\Logger\Pool::class)->getHandlers()
            );
        });
        $this->container->singleton(\Magento\MagentoCloud\Package\Manager::class);
        $this->container->singleton(\Magento\MagentoCloud\Package\MagentoVersion::class);
        $this->container->singleton(\Magento\MagentoCloud\Util\UrlManager::class);
        $this->container->singleton(
            \Magento\MagentoCloud\DB\ConnectionInterface::class,
            \Magento\MagentoCloud\DB\Connection::class
        );
        $this->container->singleton(\Magento\MagentoCloud\Filesystem\FileList::class);
        /**
         * Contextual binding.
         */
        $this->container->when(Build::class)
            ->needs(ProcessInterface::class)
            ->give(function () {
                return $this->container->makeWith(ProcessComposite::class, [
                    'processes' => [
                        $this->container->make(BuildProcess\PreBuild::class),
                        $this->container->make(BuildProcess\PrepareModuleConfig::class),
                        $this->container->make(\Magento\MagentoCloud\Process\ValidateConfiguration::class, [
                            'validators' => [
                                ValidatorInterface::LEVEL_CRITICAL => [
                                    $this->container->make(ConfigValidator\Build\ConfigFileExists::class),
                                ],
                                ValidatorInterface::LEVEL_WARNING => [
                                    $this->container->make(ConfigValidator\Build\ConfigFileStructure::class),
                                ],
                            ],
                        ]),
                        $this->container->make(BuildProcess\ApplyPatches::class),
                        $this->container->make(BuildProcess\MarshallFiles::class),
                        $this->container->make(BuildProcess\CopySampleData::class),
                        $this->container->make(BuildProcess\CompileDi::class),
                        $this->container->make(BuildProcess\ComposerDumpAutoload::class),
                        $this->container->make(BuildProcess\DeployStaticContent::class),
                        $this->container->make(BuildProcess\CompressStaticContent::class),
                        $this->container->make(BuildProcess\ClearInitDirectory::class),
                        $this->container->make(BuildProcess\BackupData::class),
                    ],
                ]);
            });
        $this->container->when(Deploy::class)
            ->needs(ProcessInterface::class)
            ->give(function () {
                return $this->container->makeWith(ProcessComposite::class, [
                    'processes' => [
                        $this->container->make(DeployProcess\PreDeploy::class),
                        $this->container->make(\Magento\MagentoCloud\Process\ValidateConfiguration::class, [
                            'validators' => [
                                ValidatorInterface::LEVEL_CRITICAL => [
                                    $this->container->make(ConfigValidator\Deploy\AdminEmail::class),
                                ],
                            ],
                        ]),
                        $this->container->make(DeployProcess\CreateConfigFile::class),
                        $this->container->make(DeployProcess\SetMode::class),
                        $this->container->make(DeployProcess\InstallUpdate::class),
                        $this->container->make(DeployProcess\DeployStaticContent::class),
                        $this->container->make(DeployProcess\CompressStaticContent::class),
                        $this->container->make(DeployProcess\DisableGoogleAnalytics::class),
                    ],
                ]);
            });
        $this->container->when(DeployProcess\InstallUpdate\Install::class)
            ->needs(ProcessInterface::class)
            ->give(function () {
                return $this->container->makeWith(ProcessComposite::class, [
                    'processes' => [
                        $this->container->make(DeployProcess\InstallUpdate\Install\Setup::class),
                        $this->container->make(DeployProcess\InstallUpdate\ConfigUpdate::class),
                        $this->container->make(DeployProcess\InstallUpdate\Install\ConfigImport::class),
                        $this->container->make(DeployProcess\InstallUpdate\Install\ResetPassword::class),
                    ],
                ]);
            });
        $this->container->when(DeployProcess\InstallUpdate\Update::class)
            ->needs(ProcessInterface::class)
            ->give(function () {
                return $this->container->makeWith(ProcessComposite::class, [
                    'processes' => [
                        $this->container->make(DeployProcess\InstallUpdate\ConfigUpdate::class),
                        $this->container->make(DeployProcess\InstallUpdate\Update\SetAdminUrl::class),
                        $this->container->make(DeployProcess\InstallUpdate\Update\Setup::class),
                        $this->container->make(DeployProcess\InstallUpdate\Update\AdminCredentials::class),
                        $this->container->make(DeployProcess\InstallUpdate\Update\ClearCache::class),
                    ],
                ]);
            });
        $this->container->when(DeployProcess\InstallUpdate\ConfigUpdate::class)
            ->needs(ProcessInterface::class)
            ->give(function () {
                return $this->container->makeWith(ProcessComposite::class, [
                    'processes' => [
                        $this->container->make(DeployProcess\InstallUpdate\ConfigUpdate\DbConnection::class),
                        $this->container->make(DeployProcess\InstallUpdate\ConfigUpdate\Amqp::class),
                        $this->container->make(DeployProcess\InstallUpdate\ConfigUpdate\Redis::class),
                        $this->container->make(DeployProcess\InstallUpdate\ConfigUpdate\SearchEngine::class),
                        $this->container->make(DeployProcess\InstallUpdate\ConfigUpdate\Urls::class),
                    ],
                ]);
            });
        $this->container->when(ConfigDump::class)
            ->needs(ProcessInterface::class)
            ->give(function () {
                return $this->container->make(ProcessComposite::class, [
                    'processes' => [
                        $this->container->make(ConfigDumpProcess\Export::class),
                        $this->container->make(ConfigDumpProcess\Generate::class),
                        $this->container->make(ConfigDumpProcess\Import::class),
                    ],
                ]);
            });
        $this->container->when(ConfigDumpProcess\Export::class)
            ->needs(ProcessInterface::class)
            ->give(function () {
                return $this->container->make(ProcessComposite::class, [
                    'processes' => [
                        $this->container->make(ConfigDumpProcess\Generate::class),
                    ],
                ]);
            });
        $this->container->when(ConfigDumpProcess\Generate::class)
            ->needs('$configKeys')
            ->give(function () {
                return [
                    'modules',
                    'scopes',
                    'system/default/general/locale/code',
                    'system/default/dev/static/sign',
                    'system/default/dev/front_end_development_workflow',
                    'system/default/dev/template',
                    'system/default/dev/js',
                    'system/default/dev/css',
                    'system/default/advanced/modules_disable_output',
                    'system/stores',
                    'system/websites',
                ];
            });
        $this->container->when(DeployProcess\PreDeploy::class)
            ->needs(ProcessInterface::class)
            ->give(function () {
                return $this->container->makeWith(ProcessComposite::class, [
                    'processes' => [
                        $this->container->make(DeployProcess\PreDeploy\RestoreWritableDirectories::class),
                        $this->container->make(DeployProcess\PreDeploy\CleanRedisCache::class),
                        $this->container->make(DeployProcess\PreDeploy\CleanFileCache::class),
                        $this->container->make(DeployProcess\PreDeploy\ProcessStaticContent::class),
                    ],
                ]);
            });
        $this->container->when(DeployProcess\DeployStaticContent::class)
            ->needs(ProcessInterface::class)
            ->give(function () {
                return $this->container->makeWith(ProcessComposite::class, [
                    'processes' => [
                        $this->get(DeployProcess\DeployStaticContent\Generate::class),
                    ],
                ]);
            });
        $this->container->when(\Magento\MagentoCloud\Config\Build::class)
            ->needs(\Magento\MagentoCloud\Filesystem\Reader\ReaderInterface::class)
            ->give(\Magento\MagentoCloud\Config\Build\Reader::class);
        $this->container->when(BuildProcess\DeployStaticContent::class)
            ->needs(ProcessInterface::class)
            ->give(function () {
                return $this->container->makeWith(ProcessComposite::class, [
                    'processes' => [
                        $this->get(BuildProcess\DeployStaticContent\Generate::class),
                    ],
                ]);
            });
        $this->container->when(DbDump::class)
            ->needs(ProcessInterface::class)
            ->give(function () {
                return $this->container->makeWith(ProcessComposite::class, [
                    'processes' => [
                        $this->container->make(DbDumpProcess\DbDump::class),
                    ],
                ]);
            });
        $this->container->when(DbDumpProcess\DbDump::class)
            ->needs(ConnectionInterface::class)
            ->give(ReadConnection::class);
        $this->container->when(PostDeploy::class)
            ->needs(ProcessInterface::class)
            ->give(function () {
                return $this->container->make(ProcessComposite::class, [
                    'processes' => [
                        $this->container->make(PostDeployProcess\CleanCache::class),
                    ],
                ]);
            });
    }

    /**
     * @inheritdoc
     */
    public function get($id)
    {
        return $this->container->make($id);
    }

    /**
     * @inheritdoc
     */
    public function has($id)
    {
        return $this->container->has($id);
    }

    /**
     * Register a binding with the container.
     *
     * @param string|array $abstract
     * @param \Closure|string|null $concrete
     * @param bool $shared
     * @return void
     */
    public function set($abstract, $concrete, bool $shared = true)
    {
        $this->container->forgetInstance($abstract);
        $this->container->bind($abstract, $concrete, $shared);
    }
}
